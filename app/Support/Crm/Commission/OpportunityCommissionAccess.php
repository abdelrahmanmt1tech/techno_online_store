<?php

namespace App\Support\Crm\Commission;

use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;

/**
 * Composes permission ($user->can), branch scope, and state rules.
 * Used by Filament resources/actions and server-side services — not Laravel Policies.
 */
final class OpportunityCommissionAccess
{
    public static function canViewAny(TenantUser $user): bool
    {
        return $user->can('crm_commissions.view_any')
            || $user->can('crm_own_commissions.view');
    }

    public static function canView(TenantUser $user, OpportunityCommission $commission): bool
    {
        $isOwner = (int) $commission->user_id === (int) $user->id;

        if ($isOwner && $user->can('crm_own_commissions.view')) {
            return true;
        }

        if (! $user->can('crm_commissions.view')) {
            return false;
        }

        if (! CrmBranchVisibility::commissionVisibleTo($user, $commission)) {
            return false;
        }

        if ($isOwner) {
            return $user->can('crm_commissions.view_own');
        }

        return $user->can('crm_commissions.view_all');
    }

    public static function canCreate(TenantUser $user): bool
    {
        return $user->can('crm_commissions.create');
    }

    public static function canUpdate(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.update')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isDirectlyEditable($commission);
    }

    public static function canDelete(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.delete')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isDeletable($commission);
    }

    public static function canRestore(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.restore')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isRestorable($commission);
    }

    public static function canForceDelete(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.force_delete')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isForceDeletable($commission);
    }

    public static function canApprove(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.approve')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isApprovable($commission);
    }

    public static function canReject(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.reject')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isRejectable($commission);
    }

    public static function canCancel(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.cancel')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isCancellable($commission);
    }

    public static function canRecalculate(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.recalculate')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isRecalculable($commission);
    }

    public static function canCreateAdjustment(TenantUser $user, OpportunityCommission $commission): bool
    {
        return OpportunityCommissionAdjustmentAccess::canCreate($user, $commission);
    }

    public static function canReverse(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.reverse')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::isReversible($commission);
    }

    public static function canChangeBaseAmount(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.change_base_amount')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::allowsBaseAmountChange($commission);
    }

    public static function canOverridePercentageLimit(TenantUser $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.override_percentage_limit')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission);
    }

    public static function canExport(TenantUser $user): bool
    {
        return $user->can('crm_commissions.export');
    }
}
