<?php

namespace App\Support\Crm\Commission;

use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Server-side guard for commission actions (services / action handlers).
 */
final class OpportunityCommissionGuard
{
    public static function ensureCanUpdate(TenantUser $user, OpportunityCommission $commission): void
    {
        if (! OpportunityCommissionAccess::canUpdate($user, $commission)) {
            throw new HttpException(403);
        }
    }

    public static function ensureCanApprove(TenantUser $user, OpportunityCommission $commission): void
    {
        if (! OpportunityCommissionAccess::canApprove($user, $commission)) {
            throw new HttpException(403);
        }
    }

    public static function ensureCanReject(TenantUser $user, OpportunityCommission $commission): void
    {
        if (! OpportunityCommissionAccess::canReject($user, $commission)) {
            throw new HttpException(403);
        }
    }

    public static function ensureCanCancel(TenantUser $user, OpportunityCommission $commission): void
    {
        if (! OpportunityCommissionAccess::canCancel($user, $commission)) {
            throw new HttpException(403);
        }
    }

    public static function ensureCanRecalculate(TenantUser $user, OpportunityCommission $commission): void
    {
        if (! OpportunityCommissionAccess::canRecalculate($user, $commission)) {
            throw new HttpException(403);
        }
    }

    public static function ensureCanCreateAdjustment(TenantUser $user, OpportunityCommission $commission): void
    {
        if (! OpportunityCommissionAccess::canCreateAdjustment($user, $commission)) {
            throw new HttpException(403);
        }
    }

    public static function ensureCanCreateForOpportunity(TenantUser $user, Opportunity $opportunity): void
    {
        if (! $user->can('crm_commissions.create')) {
            throw new HttpException(403);
        }

        if (CrmBranchVisibility::canViewAllBranches($user)) {
            return;
        }

        $branchIds = CrmBranchVisibility::branchIdsFor($user);

        if ($branchIds === [] || $opportunity->branch_id === null) {
            throw new HttpException(403);
        }

        if (! in_array((int) $opportunity->branch_id, $branchIds, true)) {
            throw new HttpException(403);
        }
    }
}
