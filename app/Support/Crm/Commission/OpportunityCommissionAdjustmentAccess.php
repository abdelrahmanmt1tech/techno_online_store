<?php

namespace App\Support\Crm\Commission;

use App\Enums\Crm\CommissionAdjustmentStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Models\Tenant\OpportunityCommissionAdjustment;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;

final class OpportunityCommissionAdjustmentAccess
{
    public static function canView(User $user, OpportunityCommissionAdjustment $adjustment): bool
    {
        if (! $user->can('crm_commissions.view_adjustments')) {
            return false;
        }

        $adjustment->loadMissing('commission');

        if ($adjustment->commission === null) {
            return false;
        }

        return CrmBranchVisibility::commissionVisibleTo($user, $adjustment->commission);
    }

    public static function canCreate(User $user, OpportunityCommission $commission): bool
    {
        return $user->can('crm_commissions.create_adjustment')
            && CrmBranchVisibility::commissionVisibleTo($user, $commission)
            && OpportunityCommissionState::allowsAdjustment($commission);
    }

    public static function canApprove(User $user, OpportunityCommissionAdjustment $adjustment): bool
    {
        if (! $user->can('crm_commissions.approve_adjustment')) {
            return false;
        }

        if ($adjustment->status !== CommissionAdjustmentStatus::PENDING) {
            return false;
        }

        $adjustment->loadMissing('commission');

        if ($adjustment->commission === null) {
            return false;
        }

        return CrmBranchVisibility::commissionVisibleTo($user, $adjustment->commission);
    }

    public static function canReject(User $user, OpportunityCommissionAdjustment $adjustment): bool
    {
        if (! $user->can('crm_commissions.reject_adjustment')) {
            return false;
        }

        if ($adjustment->status !== CommissionAdjustmentStatus::PENDING) {
            return false;
        }

        $adjustment->loadMissing('commission');

        if ($adjustment->commission === null) {
            return false;
        }

        return CrmBranchVisibility::commissionVisibleTo($user, $adjustment->commission);
    }

    public static function canCancel(User $user, OpportunityCommissionAdjustment $adjustment): bool
    {
        if (! $user->can('crm_commissions.cancel_adjustment')) {
            return false;
        }

        if ($adjustment->status !== CommissionAdjustmentStatus::PENDING) {
            return false;
        }

        $adjustment->loadMissing('commission');

        if ($adjustment->commission === null) {
            return false;
        }

        return CrmBranchVisibility::commissionVisibleTo($user, $adjustment->commission);
    }
}
