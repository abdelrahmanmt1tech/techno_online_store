<?php

namespace App\Support\Crm\Commission;

use App\Models\Tenant\CommissionPayment;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;

/**
 * Ledger entries are immutable — no update/delete permissions.
 */
final class CommissionPaymentAccess
{
    public static function canViewAny(TenantUser $user): bool
    {
        return $user->can('crm_own_commission_payments.view')
            || $user->can('crm_commission_payment_cycles.view_any');
    }

    public static function canView(TenantUser $user, CommissionPayment $payment): bool
    {
        $isOwner = (int) $payment->user_id === (int) $user->id;

        if ($isOwner && $user->can('crm_own_commission_payments.view')) {
            return true;
        }

        return $user->can('crm_commission_payment_cycles.view')
            && CrmBranchVisibility::paymentVisibleTo($user, $payment);
    }

    public static function canReversePayment(TenantUser $user, CommissionPayment $payment): bool
    {
        return $user->can('crm_commission_payment_cycles.reverse_payment')
            && CrmBranchVisibility::paymentVisibleTo($user, $payment);
    }
}
