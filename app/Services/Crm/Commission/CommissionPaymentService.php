<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionPaymentCycleStatus;
use App\Enums\Crm\CommissionPaymentEntryType;
use App\Models\Tenant\CommissionPayment;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\Tenant\CommissionPaymentCycleAllocation;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\Commission\CommissionPaymentCycleAccess;
use App\Support\Crm\Commission\CommissionPaymentCycleState;
use App\Support\Money\DecimalMath;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CommissionPaymentService
{
    /**
     * @return Collection<int, CommissionPayment>
     */
    public function executeCyclePayments(CommissionPaymentCycle $cycle, User $user): Collection
    {
        if (! CommissionPaymentCycleAccess::canPay($user, $cycle)) {
            abort(403);
        }

        return DB::transaction(function () use ($cycle, $user): Collection {
            $lockedCycle = CommissionPaymentCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->with('allocations')
                ->firstOrFail();

            if (! CommissionPaymentCycleState::isPayable($lockedCycle)) {
                throw ValidationException::withMessages([
                    'status' => __('crm.commissions.validation.cycle_not_payable'),
                ]);
            }

            if ($lockedCycle->allocations->isEmpty()) {
                throw ValidationException::withMessages([
                    'allocations' => __('crm.commissions.validation.cycle_has_no_allocations'),
                ]);
            }

            $payments = collect();

            foreach ($lockedCycle->allocations as $allocation) {
                $payments->push($this->executeAllocationPayment($lockedCycle, $allocation, $user));
            }

            $this->syncCycleStatus($lockedCycle, $user);

            CommissionAuditLogger::logFor(
                $lockedCycle->fresh(['allocations']),
                $user,
                'payments_executed',
                [],
                [
                    'payment_count' => $payments->count(),
                    'totals' => CommissionCycleTotalsCalculator::forCycle($lockedCycle->fresh()),
                ],
                null,
                CommissionCycleTotalsCalculator::forCycle($lockedCycle->fresh())['net_paid'],
            );

            return $payments;
        });
    }

    public function reversePayment(CommissionPayment $payment, User $user, string $reason): CommissionPayment
    {
        $payment->loadMissing('commissionPaymentCycle', 'opportunityCommission');

        $cycle = $payment->commissionPaymentCycle;

        if ($cycle === null || ! CommissionPaymentCycleAccess::canReversePayment($user, $cycle)) {
            abort(403);
        }

        if ($payment->entry_type !== CommissionPaymentEntryType::PAYMENT) {
            throw ValidationException::withMessages([
                'payment' => __('crm.commissions.validation.only_payments_can_be_reversed'),
            ]);
        }

        if ($payment->reversals()->exists()) {
            throw new HttpException(409, __('crm.commissions.errors.payment_already_reversed'));
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('crm.commissions.validation.reversal_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($payment, $cycle, $user, $reason): CommissionPayment {
            $lockedPayment = CommissionPayment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedPayment->reversals()->exists()) {
                throw new HttpException(409, __('crm.commissions.errors.payment_already_reversed'));
            }

            $lockedCycle = CommissionPaymentCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            $commission = OpportunityCommission::query()
                ->whereKey($lockedPayment->opportunity_commission_id)
                ->lockForUpdate()
                ->with(['adjustments', 'commissionPayments'])
                ->firstOrFail();

            $paymentAmount = DecimalMath::normalize((string) $lockedPayment->amount);
            $effectiveAmount = $commission->effectiveCommissionAmount();
            $netPaidBefore = $commission->netPaidAmount();
            $netPaidAfter = DecimalMath::sub($netPaidBefore, $paymentAmount);
            $remainingAfter = DecimalMath::remaining($effectiveAmount, $netPaidAfter);

            try {
                $reversal = CommissionPayment::query()->create([
                    'opportunity_commission_id' => $commission->id,
                    'commission_payment_cycle_id' => $lockedCycle->id,
                    'user_id' => $commission->user_id,
                    'branch_id' => $commission->branch_id,
                    'entry_type' => CommissionPaymentEntryType::REVERSAL,
                    'amount' => $paymentAmount,
                    'commission_amount_snapshot' => $effectiveAmount,
                    'paid_amount_before' => $netPaidBefore,
                    'paid_amount_after' => $netPaidAfter,
                    'remaining_amount_after' => $remainingAfter,
                    'payment_method' => $lockedPayment->payment_method,
                    'reference_number' => $lockedPayment->reference_number,
                    'executed_at' => now(),
                    'executed_by' => $user->id,
                    'reverses_payment_id' => $lockedPayment->id,
                    'reversal_reason' => $reason,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new HttpException(409, __('crm.commissions.errors.payment_already_reversed'));
            }

            $this->syncCommissionFinancialState($commission);

            $this->syncCycleStatus($lockedCycle, $user);

            CommissionAuditLogger::logFor(
                $lockedCycle->fresh(),
                $user,
                'payment_reversed',
                [
                    'payment_id' => $lockedPayment->id,
                    'amount' => $paymentAmount,
                ],
                [
                    'reversal_id' => $reversal->id,
                    'payment_id' => $lockedPayment->id,
                    'amount' => $paymentAmount,
                    'reason' => $reason,
                ],
                $netPaidBefore,
                $netPaidAfter,
            );

            CommissionAuditLogger::log(
                $commission->fresh(['adjustments', 'commissionPayments']),
                $user,
                'payment_reversed',
                [
                    'payment_id' => $lockedPayment->id,
                    'amount' => $paymentAmount,
                ],
                [
                    'reversal_id' => $reversal->id,
                    'net_paid_amount' => $commission->fresh(['adjustments', 'commissionPayments'])->netPaidAmount(),
                    'status' => $commission->fresh()->status->value,
                ],
                $netPaidBefore,
                $netPaidAfter,
            );

            return $reversal;
        });
    }

    private function executeAllocationPayment(
        CommissionPaymentCycle $cycle,
        CommissionPaymentCycleAllocation $allocation,
        User $user,
    ): CommissionPayment {
        $commission = OpportunityCommission::query()
            ->whereKey($allocation->opportunity_commission_id)
            ->lockForUpdate()
            ->with(['adjustments', 'commissionPayments'])
            ->firstOrFail();

        if (! CommissionPaymentCalculator::isCommissionPayable($commission)) {
            throw ValidationException::withMessages([
                'opportunity_commission_id' => __('crm.commissions.validation.commission_not_payable', [
                    'id' => $commission->id,
                ]),
            ]);
        }

        $paymentAmount = DecimalMath::normalize((string) $allocation->planned_payment_amount);
        $remainingAmount = $commission->remaining_amount;

        CommissionPaymentCalculator::assertPaymentAmount($paymentAmount, $remainingAmount);

        if (CommissionPaymentCalculator::isFullPayment($paymentAmount, $remainingAmount)) {
            if (! CommissionPaymentCycleAccess::canPayFull($user, $cycle)) {
                abort(403);
            }
        } elseif (! CommissionPaymentCycleAccess::canPayPartial($user, $cycle)) {
            abort(403);
        }

        $effectiveAmount = $commission->effectiveCommissionAmount();
        $netPaidBefore = $commission->netPaidAmount();
        $netPaidAfter = DecimalMath::add($netPaidBefore, $paymentAmount);
        $remainingAfter = DecimalMath::remaining($effectiveAmount, $netPaidAfter);

        $payment = CommissionPayment::query()->create([
            'opportunity_commission_id' => $commission->id,
            'commission_payment_cycle_id' => $cycle->id,
            'user_id' => $commission->user_id,
            'branch_id' => $commission->branch_id,
            'entry_type' => CommissionPaymentEntryType::PAYMENT,
            'amount' => $paymentAmount,
            'commission_amount_snapshot' => $effectiveAmount,
            'paid_amount_before' => $netPaidBefore,
            'paid_amount_after' => $netPaidAfter,
            'remaining_amount_after' => $remainingAfter,
            'payment_method' => $cycle->payment_method,
            'reference_number' => $cycle->reference_number,
            'executed_at' => now(),
            'executed_by' => $user->id,
        ]);

        $this->syncCommissionFinancialState($commission);

        CommissionAuditLogger::log(
            $commission->fresh(['adjustments', 'commissionPayments']),
            $user,
            'payment_executed',
            [
                'cycle_id' => $cycle->id,
                'allocation_id' => $allocation->id,
            ],
            [
                'payment_id' => $payment->id,
                'cycle_id' => $cycle->id,
                'amount' => $paymentAmount,
                'net_paid_amount' => $commission->fresh(['adjustments', 'commissionPayments'])->netPaidAmount(),
                'status' => $commission->fresh()->status->value,
            ],
            $netPaidBefore,
            $netPaidAfter,
        );

        return $payment;
    }

    private function syncCommissionFinancialState(OpportunityCommission $commission): void
    {
        $commission->unsetRelation('commissionPayments');
        $commission->load(['adjustments', 'commissionPayments']);

        $effectiveAmount = $commission->effectiveCommissionAmount();
        $netPaidAmount = $commission->netPaidAmount();
        $remainingAmount = DecimalMath::remaining($effectiveAmount, $netPaidAmount);
        $status = CommissionPaymentCalculator::resolveCommissionStatusAfterPayment(
            $effectiveAmount,
            $netPaidAmount,
            $remainingAmount,
        );

        $commission->update([
            'paid_amount' => $netPaidAmount,
            'status' => $status,
        ]);
    }

    private function syncCycleStatus(CommissionPaymentCycle $cycle, User $user): void
    {
        $cycle->refresh();

        if (! CommissionPaymentCycleState::hasExecutedPayments($cycle)) {
            if (in_array($cycle->status, [
                CommissionPaymentCycleStatus::PARTIALLY_PAID,
                CommissionPaymentCycleStatus::PAID,
            ], true)) {
                $cycle->update([
                    'status' => CommissionPaymentCycleStatus::APPROVED,
                    'paid_by' => null,
                ]);
            }

            return;
        }

        $totals = CommissionCycleTotalsCalculator::forCycle($cycle);
        $plannedTotal = CommissionCycleTotalsCalculator::plannedTotal($cycle);

        if (DecimalMath::isZero($totals['net_paid'])) {
            $cycle->update([
                'status' => CommissionPaymentCycleStatus::APPROVED,
                'paid_by' => null,
            ]);

            return;
        }

        $allocationsExecuted = DecimalMath::compare($totals['net_paid'], $plannedTotal) >= 0;

        $cycle->update([
            'status' => $allocationsExecuted
                ? CommissionPaymentCycleStatus::PAID
                : CommissionPaymentCycleStatus::PARTIALLY_PAID,
            'paid_by' => $user->id,
            'payment_date' => $cycle->payment_date ?? now()->toDateString(),
        ]);
    }
}
