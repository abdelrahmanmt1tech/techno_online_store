<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionAdjustmentDirection;
use App\Models\Tenant\OpportunityCommission;
use App\Support\Money\DecimalMath;
use Illuminate\Validation\ValidationException;

final class CommissionAdjustmentCalculator
{
    public static function effectiveCommissionAmount(
        string $originalCommissionAmount,
        string $approvedIncreaseTotal,
        string $approvedDecreaseTotal,
    ): string {
        return DecimalMath::sub(
            DecimalMath::add($originalCommissionAmount, $approvedIncreaseTotal),
            $approvedDecreaseTotal,
        );
    }

    public static function projectedBalanceAfter(
        string $currentEffectiveAmount,
        CommissionAdjustmentDirection $direction,
        string $amount,
    ): string {
        return match ($direction) {
            CommissionAdjustmentDirection::INCREASE => DecimalMath::add($currentEffectiveAmount, $amount),
            CommissionAdjustmentDirection::DECREASE => DecimalMath::sub($currentEffectiveAmount, $amount),
        };
    }

    public static function assertPositiveAmount(string $amount): void
    {
        if (! DecimalMath::isPositive($amount)) {
            throw ValidationException::withMessages([
                'amount' => __('crm.commissions.validation.adjustment_amount_must_be_positive'),
            ]);
        }
    }

    public static function assertDecreaseDoesNotReduceBelowNetPaid(
        string $effectiveAfterAdjustment,
        string $netPaidAmount,
    ): void {
        if (DecimalMath::compare($effectiveAfterAdjustment, $netPaidAmount) === -1) {
            throw ValidationException::withMessages([
                'amount' => __('crm.commissions.validation.adjustment_decrease_below_net_paid'),
            ]);
        }
    }

    public static function assertNonNegativeEffective(string $effectiveAmount): void
    {
        if (DecimalMath::isNegative($effectiveAmount)) {
            throw ValidationException::withMessages([
                'amount' => __('crm.commissions.validation.adjustment_negative_effective'),
            ]);
        }
    }

    public static function resolveApprovedIncreaseTotal(OpportunityCommission $commission): string
    {
        if (array_key_exists('approved_increase_adjustments_total', $commission->getAttributes())) {
            return DecimalMath::normalize($commission->getAttribute('approved_increase_adjustments_total'));
        }

        return DecimalMath::normalize(
            (string) $commission->adjustments()
                ->approved()
                ->increase()
                ->sum('amount'),
        );
    }

    public static function resolveApprovedDecreaseTotal(OpportunityCommission $commission): string
    {
        if (array_key_exists('approved_decrease_adjustments_total', $commission->getAttributes())) {
            return DecimalMath::normalize($commission->getAttribute('approved_decrease_adjustments_total'));
        }

        return DecimalMath::normalize(
            (string) $commission->adjustments()
                ->approved()
                ->decrease()
                ->sum('amount'),
        );
    }
}
