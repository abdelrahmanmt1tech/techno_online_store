<?php

namespace App\Support\Crm\Commission;

use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;

final class CommissionPaymentCycleAccess
{
    public static function canViewAny(TenantUser $user): bool
    {
        return $user->can('crm_commission_payment_cycles.view_any');
    }

    public static function canView(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.view')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle);
    }

    public static function canCreate(TenantUser $user): bool
    {
        return $user->can('crm_commission_payment_cycles.create');
    }

    public static function canUpdate(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.update')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isEditable($cycle);
    }

    public static function canDelete(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.delete')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isDeletable($cycle);
    }

    public static function canApprove(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.approve')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isApprovable($cycle);
    }

    public static function canCancel(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.cancel')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isCancellable($cycle);
    }

    public static function canPay(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isPayable($cycle);
    }

    public static function canExecutePayment(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        if (! self::canPay($user, $cycle)) {
            return false;
        }

        if (! self::canPayPartial($user, $cycle) && ! self::canPayFull($user, $cycle)) {
            return false;
        }

        $cycle->loadMissing('allocations');

        return $cycle->allocations->isNotEmpty();
    }

    public static function canPayPartial(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_partial')
            && self::canPay($user, $cycle);
    }

    public static function canPayFull(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_full')
            && self::canPay($user, $cycle);
    }

    public static function canPaySingleEmployee(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_single_employee')
            && self::canPay($user, $cycle);
    }

    public static function canPayMultipleEmployees(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_multiple_employees')
            && self::canPay($user, $cycle);
    }

    public static function canPayAllEmployees(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_all_employees')
            && self::canPay($user, $cycle);
    }

    public static function canReversePayment(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.reverse_payment')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::allowsPaymentReversal($cycle);
    }

    public static function canExport(TenantUser $user): bool
    {
        return $user->can('crm_commission_payment_cycles.export');
    }

    public static function canViewFinancialTotals(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.view_financial_totals')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle);
    }

    public static function canDownloadReceipt(TenantUser $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.download_receipt')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle);
    }
}
