<?php

namespace App\Support\Crm\Commission;

use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;

/**
 * Personal commission view access — ownership by user_id only (no branch restriction).
 */
final class OwnCommissionAccess
{
    public static function canViewPage(TenantUser $user): bool
    {
        return $user->can('crm_own_commissions.view');
    }

    public static function canViewCommission(TenantUser $user, OpportunityCommission $commission): bool
    {
        if (! self::canViewPage($user)) {
            return false;
        }

        return (int) $commission->user_id === (int) $user->id;
    }

    public static function canViewPayments(TenantUser $user): bool
    {
        return $user->can('crm_own_commission_payments.view');
    }

    public static function canExport(TenantUser $user): bool
    {
        return $user->can('crm_own_commissions.export');
    }
}
