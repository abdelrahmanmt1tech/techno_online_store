<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionPaymentCycleStatus;
use App\Models\Tenant\CommissionPayment;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\Commission\CommissionPaymentCycleAccess;
use App\Support\Crm\Commission\CommissionPaymentCycleState;
use App\Support\Crm\CrmBranchVisibility;
use App\Support\Money\DecimalMath;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommissionPaymentCycleWorkflowService
{
    /** @var list<string> */
    private const EDITABLE_FIELDS = [
        'period_from',
        'period_to',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'branch_id',
    ];

    public function __construct(
        private readonly CommissionPaymentService $paymentService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<array{opportunity_commission_id: int, user_id: int, planned_payment_amount: string}>  $allocations
     */
    public function createDraft(array $data, array $allocations, TenantUser $user): CommissionPaymentCycle
    {
        if (! CommissionPaymentCycleAccess::canCreate($user)) {
            abort(403);
        }

        $this->assertBranchAccess($user, isset($data['branch_id']) ? (int) $data['branch_id'] : null);

        if ($allocations === []) {
            throw ValidationException::withMessages([
                'allocations' => __('crm.commissions.validation.cycle_allocations_required'),
            ]);
        }

        return DB::transaction(function () use ($data, $allocations, $user): CommissionPaymentCycle {
            $referenceDate = isset($data['payment_date'])
                ? Carbon::parse($data['payment_date'])
                : now();

            $cycle = CommissionPaymentCycle::query()->create([
                'cycle_number' => CommissionPaymentCycleNumberGenerator::generate($referenceDate),
                'period_from' => $data['period_from'],
                'period_to' => $data['period_to'],
                'status' => CommissionPaymentCycleStatus::DRAFT,
                'payment_date' => $data['payment_date'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'branch_id' => $data['branch_id'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->persistAllocations($cycle, $allocations, $user);

            CommissionAuditLogger::logFor(
                $cycle->fresh(['allocations']),
                $user,
                'created',
                [],
                CommissionAuditLogger::cycleSnapshot($cycle->fresh()),
            );

            return $cycle->fresh(['allocations']);
        });
    }

    /**
     * @param  list<array{opportunity_commission_id: int, user_id: int, planned_payment_amount: string}>  $allocations
     */
    public function replaceAllocations(CommissionPaymentCycle $cycle, array $allocations, TenantUser $user): CommissionPaymentCycle
    {
        if (! CommissionPaymentCycleAccess::canUpdate($user, $cycle)) {
            abort(403);
        }

        if ($allocations === []) {
            throw ValidationException::withMessages([
                'allocations' => __('crm.commissions.validation.cycle_allocations_required'),
            ]);
        }

        return DB::transaction(function () use ($cycle, $allocations, $user): CommissionPaymentCycle {
            $locked = CommissionPaymentCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! CommissionPaymentCycleState::isEditable($locked)) {
                throw ValidationException::withMessages([
                    'status' => __('crm.commissions.validation.only_draft_cycle_can_be_edited'),
                ]);
            }

            $before = CommissionAuditLogger::cycleSnapshot($locked);

            $locked->allocations()->delete();
            $this->persistAllocations($locked, $allocations, $user);

            CommissionAuditLogger::logFor(
                $locked->fresh(['allocations']),
                $user,
                'allocations_replaced',
                $before,
                CommissionAuditLogger::cycleSnapshot($locked->fresh()),
            );

            return $locked->fresh(['allocations']);
        });
    }

    /**
     * Update a draft cycle's editable metadata. Records old/new values in the audit log, skips the
     * audit entirely when nothing changed, and refuses to touch a cycle that is no longer a draft
     * (i.e. after submit).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(CommissionPaymentCycle $cycle, array $data, TenantUser $user): CommissionPaymentCycle
    {
        if (! CommissionPaymentCycleAccess::canUpdate($user, $cycle)) {
            abort(403);
        }

        if (array_key_exists('branch_id', $data)) {
            $this->assertBranchAccess($user, $data['branch_id'] !== null && $data['branch_id'] !== ''
                ? (int) $data['branch_id']
                : null);
        }

        return DB::transaction(function () use ($cycle, $data, $user): CommissionPaymentCycle {
            $locked = CommissionPaymentCycle::query()
                ->whereKey($cycle->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! CommissionPaymentCycleState::isEditable($locked)) {
                throw ValidationException::withMessages([
                    'status' => __('crm.commissions.validation.only_draft_cycle_can_be_edited'),
                ]);
            }

            $changes = [];
            $oldValues = [];
            $newValues = [];

            foreach (self::EDITABLE_FIELDS as $field) {
                if (! array_key_exists($field, $data)) {
                    continue;
                }

                $current = $this->currentCycleValue($locked, $field);
                $incoming = $this->normalizeCycleValue($field, $data[$field]);

                if ($current === $incoming) {
                    continue;
                }

                $changes[$field] = $data[$field] === '' ? null : $data[$field];
                $oldValues[$field] = $current;
                $newValues[$field] = $incoming;
            }

            // No-op update: don't persist, don't create an audit entry.
            if ($changes === []) {
                return $locked->fresh(['allocations']);
            }

            $locked->update($changes);

            CommissionAuditLogger::logFor(
                $locked->fresh(),
                $user,
                'updated',
                $oldValues,
                $newValues,
            );

            return $locked->fresh(['allocations']);
        });
    }

    public function submitForApproval(CommissionPaymentCycle $cycle, TenantUser $user): CommissionPaymentCycle
    {
        if (! CommissionPaymentCycleAccess::canUpdate($user, $cycle)) {
            abort(403);
        }

        if (! CommissionPaymentCycleState::isSubmittable($cycle)) {
            throw ValidationException::withMessages([
                'status' => __('crm.commissions.validation.only_draft_cycle_can_be_submitted'),
            ]);
        }

        if ($cycle->allocations()->count() === 0) {
            throw ValidationException::withMessages([
                'allocations' => __('crm.commissions.validation.cycle_has_no_allocations'),
            ]);
        }

        return DB::transaction(function () use ($cycle, $user): CommissionPaymentCycle {
            $before = CommissionAuditLogger::cycleSnapshot($cycle);

            $cycle->update([
                'status' => CommissionPaymentCycleStatus::PENDING_APPROVAL,
            ]);

            CommissionAuditLogger::logFor(
                $cycle->fresh(),
                $user,
                'submitted',
                $before,
                CommissionAuditLogger::cycleSnapshot($cycle->fresh()),
            );

            return $cycle->refresh();
        });
    }

    public function approve(CommissionPaymentCycle $cycle, TenantUser $user): CommissionPaymentCycle
    {
        if (! CommissionPaymentCycleAccess::canApprove($user, $cycle)) {
            abort(403);
        }

        return DB::transaction(function () use ($cycle, $user): CommissionPaymentCycle {
            $before = CommissionAuditLogger::cycleSnapshot($cycle);

            $cycle->update([
                'status' => CommissionPaymentCycleStatus::APPROVED,
                'approved_by' => $user->id,
            ]);

            CommissionAuditLogger::logFor(
                $cycle->fresh(),
                $user,
                'approved',
                $before,
                CommissionAuditLogger::cycleSnapshot($cycle->fresh()),
            );

            return $cycle->refresh();
        });
    }

    public function cancel(CommissionPaymentCycle $cycle, TenantUser $user, string $reason): CommissionPaymentCycle
    {
        if (! CommissionPaymentCycleAccess::canCancel($user, $cycle)) {
            abort(403);
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('crm.commissions.validation.cancellation_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($cycle, $user, $reason): CommissionPaymentCycle {
            $before = CommissionAuditLogger::cycleSnapshot($cycle);

            $cycle->update([
                'status' => CommissionPaymentCycleStatus::CANCELLED,
            ]);

            CommissionAuditLogger::logFor(
                $cycle->fresh(),
                $user,
                'cancelled',
                $before,
                array_merge(CommissionAuditLogger::cycleSnapshot($cycle->fresh()), ['reason' => $reason]),
            );

            return $cycle->refresh();
        });
    }

    /**
     * @return Collection<int, CommissionPayment>
     */
    public function executePayments(CommissionPaymentCycle $cycle, TenantUser $user)
    {
        if (! CommissionPaymentCycleAccess::canPay($user, $cycle)) {
            abort(403);
        }

        return $this->paymentService->executeCyclePayments($cycle, $user);
    }

    /**
     * @param  list<array{opportunity_commission_id: int, user_id: int, planned_payment_amount: string}>  $allocations
     */
    private function persistAllocations(CommissionPaymentCycle $cycle, array $allocations, TenantUser $user): void
    {
        $seenCommissionIds = [];

        foreach ($allocations as $allocationData) {
            $commission = OpportunityCommission::query()
                ->with(['adjustments', 'commissionPayments'])
                ->findOrFail($allocationData['opportunity_commission_id']);

            if (! CrmBranchVisibility::commissionVisibleTo($user, $commission)) {
                abort(403);
            }

            if ((int) $allocationData['user_id'] !== (int) $commission->user_id) {
                throw ValidationException::withMessages([
                    'user_id' => __('crm.commissions.validation.allocation_user_mismatch'),
                ]);
            }

            if (in_array($commission->id, $seenCommissionIds, true)) {
                throw ValidationException::withMessages([
                    'opportunity_commission_id' => __('crm.commissions.validation.duplicate_cycle_allocation'),
                ]);
            }

            $seenCommissionIds[] = $commission->id;

            if (! CommissionPaymentCalculator::isCommissionPayable($commission)) {
                throw ValidationException::withMessages([
                    'opportunity_commission_id' => __('crm.commissions.validation.commission_not_payable', [
                        'id' => $commission->id,
                    ]),
                ]);
            }

            $plannedAmount = DecimalMath::normalize((string) $allocationData['planned_payment_amount']);
            $remainingAmount = $commission->remaining_amount;

            CommissionPaymentCalculator::assertPaymentAmount($plannedAmount, $remainingAmount);

            $cycle->allocations()->create([
                'opportunity_commission_id' => $commission->id,
                'user_id' => $commission->user_id,
                'effective_amount_snapshot' => $commission->effectiveCommissionAmount(),
                'net_paid_snapshot' => $commission->netPaidAmount(),
                'remaining_snapshot' => $remainingAmount,
                'planned_payment_amount' => $plannedAmount,
            ]);
        }
    }

    private function currentCycleValue(CommissionPaymentCycle $cycle, string $field): ?string
    {
        $value = match ($field) {
            'period_from' => $cycle->period_from?->toDateString(),
            'period_to' => $cycle->period_to?->toDateString(),
            'payment_date' => $cycle->payment_date?->toDateString(),
            'branch_id' => $cycle->branch_id !== null ? (string) $cycle->branch_id : null,
            default => $cycle->{$field},
        };

        return ($value === null || $value === '') ? null : (string) $value;
    }

    private function normalizeCycleValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'period_from', 'period_to', 'payment_date' => Carbon::parse((string) $value)->toDateString(),
            'branch_id' => (string) (int) $value,
            default => (string) $value,
        };
    }

    private function assertBranchAccess(TenantUser $user, ?int $branchId): void
    {
        if ($branchId === null || CrmBranchVisibility::canViewAllBranches($user)) {
            return;
        }

        $branchIds = CrmBranchVisibility::branchIdsFor($user);

        if ($branchIds === [] || ! in_array($branchId, $branchIds, true)) {
            abort(403);
        }
    }
}
