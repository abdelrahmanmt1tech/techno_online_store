<?php

namespace App\Support\Crm;

use App\Models\Tenant\CommissionPayment;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Builder;

class CrmBranchVisibility
{
    public static function canViewAllBranches(TenantUser $user): bool
    {
        return $user->can('crm_reports.view_all_branches');
    }

    /**
     * @return list<int>
     */
    public static function branchIdsFor(TenantUser $user): array
    {
        return $user->branches()
            ->pluck('branches.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function commissionVisibleTo(TenantUser $user, OpportunityCommission $commission): bool
    {
        if (self::canViewAllBranches($user)) {
            return true;
        }

        $branchIds = self::branchIdsFor($user);

        if ($branchIds === []) {
            return false;
        }

        if ($commission->branch_id === null) {
            return false;
        }

        return in_array((int) $commission->branch_id, $branchIds, true);
    }

    public static function paymentVisibleTo(TenantUser $user, CommissionPayment $payment): bool
    {
        if (self::canViewAllBranches($user)) {
            return true;
        }

        $branchIds = self::branchIdsFor($user);

        if ($branchIds === []) {
            return false;
        }

        if ($payment->branch_id !== null) {
            return in_array((int) $payment->branch_id, $branchIds, true);
        }

        $payment->loadMissing('opportunityCommission');

        if ($payment->opportunityCommission === null) {
            return false;
        }

        return self::commissionVisibleTo($user, $payment->opportunityCommission);
    }

    public static function cycleVisibleTo(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        if (self::canViewAllBranches($user)) {
            return true;
        }

        $branchIds = self::branchIdsFor($user);

        if ($branchIds === []) {
            return false;
        }

        $cycle->loadCount('commissionPayments');

        if ($cycle->commission_payments_count === 0) {
            return $cycle->branch_id === null
                || in_array((int) $cycle->branch_id, $branchIds, true);
        }

        $foreignPaymentExists = $cycle->commissionPayments()
            ->where(function (Builder $payment) use ($branchIds): void {
                $payment
                    ->where(function (Builder $scoped) use ($branchIds): void {
                        $scoped
                            ->whereNotNull('branch_id')
                            ->whereNotIn('branch_id', $branchIds);
                    })
                    ->orWhereHas('opportunityCommission', function (Builder $commission) use ($branchIds): void {
                        $commission->whereNotIn('branch_id', $branchIds);
                    });
            })
            ->exists();

        return ! $foreignPaymentExists;
    }

    /**
     * @param  Builder<OpportunityCommission>  $query
     * @return Builder<OpportunityCommission>
     */
    public static function applyCommissionScope(Builder $query, TenantUser $user): Builder
    {
        if (self::canViewAllBranches($user)) {
            return $query;
        }

        $branchIds = self::branchIdsFor($user);

        if ($branchIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * @param  Builder<CommissionPayment>  $query
     * @return Builder<CommissionPayment>
     */
    public static function applyPaymentScope(Builder $query, TenantUser $user): Builder
    {
        if (self::canViewAllBranches($user)) {
            return $query;
        }

        $branchIds = self::branchIdsFor($user);

        if ($branchIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $scoped) use ($branchIds): void {
            $scoped
                ->whereIn('branch_id', $branchIds)
                ->orWhereHas('opportunityCommission', function (Builder $commission) use ($branchIds): void {
                    $commission->whereIn('branch_id', $branchIds);
                });
        });
    }

    /**
     * @param  Builder<CommissionPaymentCycle>  $query
     * @return Builder<CommissionPaymentCycle>
     */
    public static function applyCycleScope(Builder $query, TenantUser $user): Builder
    {
        if (self::canViewAllBranches($user)) {
            return $query;
        }

        $branchIds = self::branchIdsFor($user);

        if ($branchIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $scoped) use ($branchIds): void {
            $scoped
                ->where(function (Builder $draft) use ($branchIds): void {
                    $draft
                        ->doesntHave('commissionPayments')
                        ->where(function (Builder $branchScoped) use ($branchIds): void {
                            $branchScoped
                                ->whereNull('branch_id')
                                ->orWhereIn('branch_id', $branchIds);
                        });
                })
                ->orWhere(function (Builder $withPayments) use ($branchIds): void {
                    $withPayments
                        ->whereHas('commissionPayments')
                        ->whereDoesntHave('commissionPayments', function (Builder $payment) use ($branchIds): void {
                            $payment
                                ->where(function (Builder $scoped) use ($branchIds): void {
                                    $scoped
                                        ->whereNotNull('branch_id')
                                        ->whereNotIn('branch_id', $branchIds);
                                })
                                ->orWhereHas('opportunityCommission', function (Builder $commission) use ($branchIds): void {
                                    $commission->whereNotIn('branch_id', $branchIds);
                                });
                        });
                });
        });
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public static function applyOpportunityScope(Builder $query, TenantUser $user): Builder
    {
        if (self::canViewAllBranches($user)) {
            return $query;
        }

        $branchIds = self::branchIdsFor($user);

        if ($branchIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('branch_id', $branchIds);
    }
}
