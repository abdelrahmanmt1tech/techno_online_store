<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use App\Support\Money\DecimalMath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class CommissionPaymentCycleCommissionSelector
{
    /**
     * @param  list<int>  $employeeIds  empty = all employees in scope
     * @return Builder<OpportunityCommission>
     */
    public static function buildQuery(
        User $user,
        Carbon $periodFrom,
        Carbon $periodTo,
        ?int $branchId = null,
        array $employeeIds = [],
    ): Builder {
        $query = OpportunityCommission::query()
            ->withFinancialAggregates()
            ->with(['user', 'opportunity.client'])
            ->visibleToUser($user)
            ->whereIn('status', [
                CommissionStatus::APPROVED,
                CommissionStatus::PARTIALLY_PAID,
                CommissionStatus::PAID,
            ])
            ->where(function (Builder $period) use ($periodFrom, $periodTo): void {
                $period
                    ->whereNull('due_at')
                    ->orWhereBetween('due_at', [$periodFrom->toDateString(), $periodTo->toDateString()]);
            });

        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        if ($employeeIds !== []) {
            $query->whereIn('user_id', $employeeIds);
        }

        return $query;
    }

    /**
     * @param  list<int>  $employeeIds
     * @return list<OpportunityCommission>
     */
    public static function payableCommissions(
        User $user,
        Carbon $periodFrom,
        Carbon $periodTo,
        ?int $branchId = null,
        array $employeeIds = [],
    ): array {
        if (! CrmBranchVisibility::canViewAllBranches($user) && CrmBranchVisibility::branchIdsFor($user) === []) {
            return [];
        }

        if ($branchId !== null && ! CrmBranchVisibility::canViewAllBranches($user)) {
            $branchIds = CrmBranchVisibility::branchIdsFor($user);

            if (! in_array($branchId, $branchIds, true)) {
                return [];
            }
        }

        return self::buildQuery($user, $periodFrom, $periodTo, $branchId, $employeeIds)
            ->orderBy('due_at')
            ->get()
            ->filter(fn (OpportunityCommission $commission): bool => DecimalMath::isPositive($commission->remaining_amount))
            ->values()
            ->all();
    }
}
