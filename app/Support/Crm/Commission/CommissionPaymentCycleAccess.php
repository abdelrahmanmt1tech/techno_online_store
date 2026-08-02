<?php

namespace App\Support\Crm\Commission;

use App\Models\Tenant\CommissionPaymentCycle;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;

final class CommissionPaymentCycleAccess
{
    public static function canViewAny(User $user): bool
    {
        return $user->can('crm_commission_payment_cycles.view_any');
    }

    public static function canView(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.view')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle);
    }

    public static function canCreate(User $user): bool
    {
        return $user->can('crm_commission_payment_cycles.create');
    }

    public static function canUpdate(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.update')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isEditable($cycle);
    }

    public static function canDelete(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.delete')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isDeletable($cycle);
    }

    public static function canApprove(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.approve')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isApprovable($cycle);
    }

    public static function canCancel(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.cancel')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isCancellable($cycle);
    }

    public static function canPay(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::isPayable($cycle);
    }

    public static function canExecutePayment(User $user, CommissionPaymentCycle $cycle): bool
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

    public static function canPayPartial(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_partial')
            && self::canPay($user, $cycle);
    }

    public static function canPayFull(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_full')
            && self::canPay($user, $cycle);
    }

    public static function canPaySingleEmployee(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_single_employee')
            && self::canPay($user, $cycle);
    }

    public static function canPayMultipleEmployees(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_multiple_employees')
            && self::canPay($user, $cycle);
    }

    public static function canPayAllEmployees(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.pay_all_employees')
            && self::canPay($user, $cycle);
    }

    public static function canReversePayment(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.reverse_payment')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle)
            && CommissionPaymentCycleState::allowsPaymentReversal($cycle);
    }

    public static function canExport(User $user): bool
    {
        return $user->can('crm_commission_payment_cycles.export');
    }

    public static function canViewFinancialTotals(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.view_financial_totals')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle);
    }

    public static function canDownloadReceipt(User $user, CommissionPaymentCycle $cycle): bool
    {
        return $user->can('crm_commission_payment_cycles.download_receipt')
            && CrmBranchVisibility::cycleVisibleTo($user, $cycle);
    }
}
