<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\Commission\OpportunityCommissionAccess;
use App\Support\Crm\Commission\OpportunityCommissionGuard;
use App\Support\Crm\Commission\OpportunityCommissionState;
use App\Support\Crm\CrmBranchVisibility;
use App\Support\Money\DecimalMath;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpportunityCommissionWorkflowService
{
    public function canCreateForOpportunity(User $user, Opportunity $opportunity): bool
    {
        if (! $user->can('crm_commissions.create')) {
            return false;
        }

        if (CrmBranchVisibility::canViewAllBranches($user)) {
            return true;
        }

        $branchIds = CrmBranchVisibility::branchIdsFor($user);

        if ($branchIds === [] || $opportunity->branch_id === null) {
            return false;
        }

        return in_array((int) $opportunity->branch_id, $branchIds, true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): OpportunityCommission
    {
        if (! $user->can('crm_commissions.create')) {
            abort(403);
        }

        $opportunity = Opportunity::query()->findOrFail($data['opportunity_id']);

        if (! $this->canCreateForOpportunity($user, $opportunity)) {
            abort(403);
        }

        $this->validateFinancialFields($data, $user);

        return DB::transaction(function () use ($data, $user, $opportunity): OpportunityCommission {
            $commission = OpportunityCommission::query()->create([
                'opportunity_id' => $opportunity->id,
                'user_id' => $data['user_id'],
                'branch_id' => $opportunity->branch_id,
                'commission_type' => $data['commission_type'],
                'base_amount' => $data['base_amount'],
                'commission_percentage' => $data['commission_percentage'],
                'commission_amount' => $data['commission_amount'],
                'status' => CommissionStatus::DRAFT,
                'notes' => $data['notes'] ?? null,
                'due_at' => $data['due_at'] ?? null,
                'last_manual_edit_field' => $data['last_manual_edit_field'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'created',
                [],
                CommissionAuditLogger::snapshot($commission),
                null,
                (string) $commission->commission_amount,
            );

            return $commission;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OpportunityCommission $commission, array $data, User $user): OpportunityCommission
    {
        OpportunityCommissionGuard::ensureCanUpdate($user, $commission);

        if (isset($data['base_amount']) && (string) $data['base_amount'] !== (string) $commission->base_amount) {
            if (! OpportunityCommissionAccess::canChangeBaseAmount($user, $commission)) {
                abort(403);
            }
        }

        $this->validateFinancialFields($data, $user, $commission);

        return DB::transaction(function () use ($commission, $data, $user): OpportunityCommission {
            $before = CommissionAuditLogger::snapshot($commission);

            $commission->fill([
                'user_id' => $data['user_id'] ?? $commission->user_id,
                'commission_type' => $data['commission_type'] ?? $commission->commission_type,
                'base_amount' => $data['base_amount'] ?? $commission->base_amount,
                'commission_percentage' => $data['commission_percentage'] ?? $commission->commission_percentage,
                'commission_amount' => $data['commission_amount'] ?? $commission->commission_amount,
                'notes' => $data['notes'] ?? $commission->notes,
                'due_at' => array_key_exists('due_at', $data) ? $data['due_at'] : $commission->due_at,
                'last_manual_edit_field' => $data['last_manual_edit_field'] ?? $commission->last_manual_edit_field,
                'updated_by' => $user->id,
            ]);
            $commission->save();

            CommissionAuditLogger::log(
                $commission,
                $user,
                'updated',
                $before,
                CommissionAuditLogger::snapshot($commission),
                $before['commission_amount'],
                (string) $commission->commission_amount,
            );

            return $commission->refresh();
        });
    }

    public function submitForApproval(OpportunityCommission $commission, User $user): OpportunityCommission
    {
        if (! $user->can('crm_commissions.update') || ! OpportunityCommissionState::isDirectlyEditable($commission)) {
            abort(403);
        }

        if (! CrmBranchVisibility::commissionVisibleTo($user, $commission)) {
            abort(403);
        }

        if ($commission->status !== CommissionStatus::DRAFT) {
            throw ValidationException::withMessages([
                'status' => __('crm.commissions.validation.only_draft_can_be_submitted'),
            ]);
        }

        return DB::transaction(function () use ($commission, $user): OpportunityCommission {
            $before = CommissionAuditLogger::snapshot($commission);

            $commission->update([
                'status' => CommissionStatus::PENDING,
                'updated_by' => $user->id,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'submitted',
                $before,
                CommissionAuditLogger::snapshot($commission),
            );

            return $commission->refresh();
        });
    }

    public function approve(OpportunityCommission $commission, User $user): OpportunityCommission
    {
        OpportunityCommissionGuard::ensureCanApprove($user, $commission);

        return DB::transaction(function () use ($commission, $user): OpportunityCommission {
            $before = CommissionAuditLogger::snapshot($commission);

            $commission->update([
                'status' => CommissionStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'approved',
                $before,
                CommissionAuditLogger::snapshot($commission),
            );

            return $commission->refresh();
        });
    }

    public function reject(OpportunityCommission $commission, User $user, string $reason): OpportunityCommission
    {
        if (! OpportunityCommissionAccess::canReject($user, $commission)) {
            abort(403);
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('crm.commissions.validation.rejection_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($commission, $user, $reason): OpportunityCommission {
            $before = CommissionAuditLogger::snapshot($commission);

            $commission->update([
                'status' => CommissionStatus::REJECTED,
                'updated_by' => $user->id,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'rejected',
                $before,
                array_merge(CommissionAuditLogger::snapshot($commission), ['reason' => $reason]),
            );

            return $commission->refresh();
        });
    }

    public function cancel(OpportunityCommission $commission, User $user, string $reason): OpportunityCommission
    {
        if (! OpportunityCommissionAccess::canCancel($user, $commission)) {
            abort(403);
        }

        if (DecimalMath::isPositive($commission->paid_amount)) {
            throw ValidationException::withMessages([
                'status' => __('crm.commissions.validation.cannot_cancel_with_payments'),
            ]);
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('crm.commissions.validation.cancellation_reason_required'),
            ]);
        }

        return DB::transaction(function () use ($commission, $user, $reason): OpportunityCommission {
            $before = CommissionAuditLogger::snapshot($commission);

            $commission->update([
                'status' => CommissionStatus::CANCELLED,
                'updated_by' => $user->id,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'cancelled',
                $before,
                array_merge(CommissionAuditLogger::snapshot($commission), ['reason' => $reason]),
            );

            return $commission->refresh();
        });
    }

    public function previewRecalculate(OpportunityCommission $commission): array
    {
        $opportunity = $commission->opportunity()->firstOrFail();
        $newBase = CommissionCalculator::defaultBaseAmount($opportunity);
        $newAmount = CommissionCalculator::amountFromPercentage($newBase, (string) $commission->commission_percentage);

        return [
            'base_amount' => $newBase,
            'commission_percentage' => (string) $commission->commission_percentage,
            'commission_amount' => $newAmount,
        ];
    }

    public function recalculate(OpportunityCommission $commission, User $user): OpportunityCommission
    {
        if (! OpportunityCommissionAccess::canRecalculate($user, $commission)) {
            abort(403);
        }

        $preview = $this->previewRecalculate($commission);

        return DB::transaction(function () use ($commission, $user, $preview): OpportunityCommission {
            $before = CommissionAuditLogger::snapshot($commission);

            $commission->update([
                'base_amount' => $preview['base_amount'],
                'commission_amount' => $preview['commission_amount'],
                'calculated_at' => now(),
                'updated_by' => $user->id,
            ]);

            CommissionAuditLogger::log(
                $commission,
                $user,
                'recalculated',
                $before,
                CommissionAuditLogger::snapshot($commission),
                $before['commission_amount'],
                $preview['commission_amount'],
            );

            return $commission->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateFinancialFields(array $data, User $user, ?OpportunityCommission $commission = null): void
    {
        if (! isset($data['base_amount'], $data['commission_percentage'], $data['commission_amount'])) {
            return;
        }

        CommissionCalculator::assertBaseAmount((string) $data['base_amount']);
        CommissionCalculator::assertNonNegative((string) $data['commission_amount'], 'commission_amount');
        CommissionCalculator::assertPercentageWithinLimit(
            (string) $data['commission_percentage'],
            $user,
            $commission,
        );
    }
}
