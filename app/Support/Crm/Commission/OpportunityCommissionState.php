<?php

namespace App\Support\Crm\Commission;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Support\Money\DecimalMath;

final class OpportunityCommissionState
{
    public static function isDirectlyEditable(OpportunityCommission $commission): bool
    {
        return in_array($commission->status, [
            CommissionStatus::DRAFT,
            CommissionStatus::PENDING,
        ], true);
    }

    public static function isDeletable(OpportunityCommission $commission): bool
    {
        return $commission->status === CommissionStatus::DRAFT;
    }

    public static function isRestorable(OpportunityCommission $commission): bool
    {
        return in_array($commission->status, [
            CommissionStatus::CANCELLED,
            CommissionStatus::REJECTED,
        ], true);
    }

    public static function isForceDeletable(OpportunityCommission $commission): bool
    {
        return in_array($commission->status, [
            CommissionStatus::DRAFT,
            CommissionStatus::CANCELLED,
            CommissionStatus::REJECTED,
        ], true);
    }

    public static function isApprovable(OpportunityCommission $commission): bool
    {
        return $commission->status === CommissionStatus::PENDING;
    }

    public static function isRejectable(OpportunityCommission $commission): bool
    {
        return $commission->status === CommissionStatus::PENDING;
    }

    public static function isCancellable(OpportunityCommission $commission): bool
    {
        if (in_array($commission->status, [
            CommissionStatus::PARTIALLY_PAID,
            CommissionStatus::PAID,
        ], true)) {
            return false;
        }

        if (DecimalMath::isPositive($commission->paid_amount)) {
            return false;
        }

        return true;
    }

    public static function isRecalculable(OpportunityCommission $commission): bool
    {
        return in_array($commission->status, [
            CommissionStatus::DRAFT,
            CommissionStatus::PENDING,
        ], true);
    }

    public static function allowsAdjustment(OpportunityCommission $commission): bool
    {
        return in_array($commission->status, [
            CommissionStatus::APPROVED,
            CommissionStatus::PARTIALLY_PAID,
            CommissionStatus::PAID,
        ], true);
    }

    public static function isReversible(OpportunityCommission $commission): bool
    {
        return in_array($commission->status, [
            CommissionStatus::PARTIALLY_PAID,
            CommissionStatus::PAID,
        ], true);
    }

    public static function allowsBaseAmountChange(OpportunityCommission $commission): bool
    {
        return in_array($commission->status, [
            CommissionStatus::DRAFT,
            CommissionStatus::PENDING,
        ], true);
    }

    public static function isPayable(OpportunityCommission $commission): bool
    {
        if (! in_array($commission->status, [
            CommissionStatus::APPROVED,
            CommissionStatus::PARTIALLY_PAID,
            CommissionStatus::PAID,
        ], true)) {
            return false;
        }

        return DecimalMath::isPositive($commission->remaining_amount);
    }
}
