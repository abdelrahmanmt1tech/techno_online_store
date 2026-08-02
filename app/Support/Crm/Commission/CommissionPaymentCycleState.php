<?php

namespace App\Support\Crm\Commission;

use App\Enums\Crm\CommissionPaymentCycleStatus;
use App\Models\Tenant\CommissionPaymentCycle;

final class CommissionPaymentCycleState
{
    public static function isEditable(CommissionPaymentCycle $cycle): bool
    {
        return $cycle->status === CommissionPaymentCycleStatus::DRAFT;
    }

    public static function isDeletable(CommissionPaymentCycle $cycle): bool
    {
        return $cycle->status === CommissionPaymentCycleStatus::DRAFT;
    }

    public static function isApprovable(CommissionPaymentCycle $cycle): bool
    {
        return $cycle->status === CommissionPaymentCycleStatus::PENDING_APPROVAL;
    }

    public static function isSubmittable(CommissionPaymentCycle $cycle): bool
    {
        return $cycle->status === CommissionPaymentCycleStatus::DRAFT;
    }

    public static function isCancellable(CommissionPaymentCycle $cycle): bool
    {
        if (in_array($cycle->status, [
            CommissionPaymentCycleStatus::PAID,
            CommissionPaymentCycleStatus::PARTIALLY_PAID,
            CommissionPaymentCycleStatus::CANCELLED,
        ], true)) {
            return false;
        }

        if ($cycle->status === CommissionPaymentCycleStatus::APPROVED) {
            return ! self::hasExecutedPayments($cycle);
        }

        return true;
    }

    public static function hasExecutedPayments(CommissionPaymentCycle $cycle): bool
    {
        if ($cycle->relationLoaded('commissionPayments')) {
            return $cycle->commissionPayments->isNotEmpty();
        }

        return $cycle->commissionPayments()->exists();
    }

    public static function isPayable(CommissionPaymentCycle $cycle): bool
    {
        return $cycle->status === CommissionPaymentCycleStatus::APPROVED;
    }

    public static function allowsPaymentReversal(CommissionPaymentCycle $cycle): bool
    {
        return in_array($cycle->status, [
            CommissionPaymentCycleStatus::PARTIALLY_PAID,
            CommissionPaymentCycleStatus::PAID,
        ], true);
    }
}
