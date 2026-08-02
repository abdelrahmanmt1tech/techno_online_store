<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionAdjustmentDirection;
use App\Enums\Crm\CommissionAdjustmentStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Models\Tenant\OpportunityCommissionAdjustment;
use App\Models\TenantUser;
use App\Support\Crm\Commission\OpportunityCommissionAdjustmentAccess;
use App\Support\Money\DecimalMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OpportunityCommissionAdjustmentWorkflowService
{
    public function create(
        OpportunityCommission $commission,
        TenantUser $user,
        CommissionAdjustmentDirection $direction,
        string $amount,
        string $reason,
    ): OpportunityCommissionAdjustment {
        if (! OpportunityCommissionAdjustmentAccess::canCreate($user, $commission)) {
            abort(403);
        }

        $amount = DecimalMath::normalize($amount);
        $reason = trim($reason);

        CommissionAdjustmentCalculator::assertPositiveAmount($amount);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('crm.commissions.validation.adjustment_reason_required'),
            ]);
        }

        $commission->loadMissing('adjustments', 'commissionPayments');

        $balanceBefore = $commission->effectiveCommissionAmount();
        $balanceAfter = CommissionAdjustmentCalculator::projectedBalanceAfter($balanceBefore, $direction, $amount);

        CommissionAdjustmentCalculator::assertNonNegativeEffective($balanceAfter);

        if ($direction === CommissionAdjustmentDirection::DECREASE) {
            CommissionAdjustmentCalculator::assertDecreaseDoesNotReduceBelowNetPaid(
                $balanceAfter,
                $commission->netPaidAmount(),
            );
        }

        return DB::transaction(function () use ($commission, $user, $direction, $amount, $reason, $balanceBefore, $balanceAfter): OpportunityCommissionAdjustment {
            $adjustment = $commission->adjustments()->create([
                'direction' => $direction,
                'amount' => $amount,
                'reason' => $reason,
                'status' => CommissionAdjustmentStatus::PENDING,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'created_by' => $user->id,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'adjustment_created',
                [],
                [
                    'adjustment_id' => $adjustment->id,
                    'direction' => $direction->value,
                    'amount' => $amount,
                    'status' => CommissionAdjustmentStatus::PENDING->value,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reason' => $reason,
                ],
                $balanceBefore,
                $balanceAfter,
            );

            return $adjustment;
        });
    }

    public function approve(OpportunityCommissionAdjustment $adjustment, TenantUser $user): OpportunityCommissionAdjustment
    {
        return DB::transaction(function () use ($adjustment, $user): OpportunityCommissionAdjustment {
            $locked = OpportunityCommissionAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== CommissionAdjustmentStatus::PENDING) {
                throw new HttpException(409, __('crm.commissions.errors.adjustment_not_pending'));
            }

            if (! OpportunityCommissionAdjustmentAccess::canApprove($user, $locked)) {
                abort(403);
            }

            $commission = OpportunityCommission::query()
                ->whereKey($locked->opportunity_commission_id)
                ->lockForUpdate()
                ->with(['adjustments', 'commissionPayments'])
                ->firstOrFail();

            $effectiveBefore = $commission->effectiveCommissionAmount();
            $effectiveAfter = CommissionAdjustmentCalculator::projectedBalanceAfter(
                $effectiveBefore,
                $locked->direction,
                (string) $locked->amount,
            );

            CommissionAdjustmentCalculator::assertNonNegativeEffective($effectiveAfter);

            if ($locked->direction === CommissionAdjustmentDirection::DECREASE) {
                CommissionAdjustmentCalculator::assertDecreaseDoesNotReduceBelowNetPaid(
                    $effectiveAfter,
                    $commission->netPaidAmount(),
                );
            }

            $locked->update([
                'status' => CommissionAdjustmentStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'balance_before' => $effectiveBefore,
                'balance_after' => $effectiveAfter,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'adjustment_approved',
                [
                    'adjustment_id' => $locked->id,
                    'effective_commission_amount' => $effectiveBefore,
                ],
                [
                    'adjustment_id' => $locked->id,
                    'direction' => $locked->direction->value,
                    'amount' => (string) $locked->amount,
                    'status' => CommissionAdjustmentStatus::APPROVED->value,
                    'effective_commission_amount' => $effectiveAfter,
                ],
                $effectiveBefore,
                $effectiveAfter,
            );

            return $locked->refresh();
        });
    }

    public function reject(
        OpportunityCommissionAdjustment $adjustment,
        TenantUser $user,
        string $rejectionReason,
    ): OpportunityCommissionAdjustment {
        $rejectionReason = trim($rejectionReason);

        if ($rejectionReason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => __('crm.commissions.validation.adjustment_rejection_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($adjustment, $user, $rejectionReason): OpportunityCommissionAdjustment {
            $locked = OpportunityCommissionAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== CommissionAdjustmentStatus::PENDING) {
                throw new HttpException(409, __('crm.commissions.errors.adjustment_not_pending'));
            }

            if (! OpportunityCommissionAdjustmentAccess::canReject($user, $locked)) {
                abort(403);
            }

            $commission = $locked->commission()->firstOrFail();

            $locked->update([
                'status' => CommissionAdjustmentStatus::REJECTED,
                'rejected_by' => $user->id,
                'rejected_at' => now(),
                'rejection_reason' => $rejectionReason,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'adjustment_rejected',
                ['adjustment_id' => $locked->id, 'status' => CommissionAdjustmentStatus::PENDING->value],
                [
                    'adjustment_id' => $locked->id,
                    'status' => CommissionAdjustmentStatus::REJECTED->value,
                    'rejection_reason' => $rejectionReason,
                ],
            );

            return $locked->refresh();
        });
    }

    public function cancel(OpportunityCommissionAdjustment $adjustment, TenantUser $user): OpportunityCommissionAdjustment
    {
        return DB::transaction(function () use ($adjustment, $user): OpportunityCommissionAdjustment {
            $locked = OpportunityCommissionAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== CommissionAdjustmentStatus::PENDING) {
                throw new HttpException(409, __('crm.commissions.errors.adjustment_not_pending'));
            }

            if (! OpportunityCommissionAdjustmentAccess::canCancel($user, $locked)) {
                abort(403);
            }

            $commission = $locked->commission()->firstOrFail();

            $locked->update([
                'status' => CommissionAdjustmentStatus::CANCELLED,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'adjustment_cancelled',
                ['adjustment_id' => $locked->id, 'status' => CommissionAdjustmentStatus::PENDING->value],
                ['adjustment_id' => $locked->id, 'status' => CommissionAdjustmentStatus::CANCELLED->value],
            );

            return $locked->refresh();
        });
    }
}
