<?php

namespace App\Services\Crm\Commission;

use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Support\Crm\Commission\OpportunityCommissionAccess;
use App\Support\Money\DecimalMath;
use Illuminate\Validation\ValidationException;

final class CommissionCalculator
{
    public static function maxPercentage(): string
    {
        return DecimalMath::normalize(config('crm.commission_max_percentage', '25.00'), DecimalMath::RATE_SCALE);
    }

    public static function defaultBaseAmount(Opportunity $opportunity): string
    {
        if ($opportunity->agreed_amount !== null && ! DecimalMath::isZero($opportunity->agreed_amount)) {
            return DecimalMath::normalize($opportunity->agreed_amount);
        }

        if ($opportunity->amount !== null && ! DecimalMath::isZero($opportunity->amount)) {
            return DecimalMath::normalize($opportunity->amount);
        }

        return DecimalMath::zero();
    }

    public static function amountFromPercentage(string $baseAmount, string $percentage): string
    {
        return DecimalMath::percentOfAmount($baseAmount, $percentage);
    }

    public static function percentageFromAmount(string $baseAmount, string $commissionAmount): string
    {
        if (DecimalMath::isZero($baseAmount)) {
            throw ValidationException::withMessages([
                'base_amount' => __('crm.commissions.validation.base_amount_zero'),
            ]);
        }

        return DecimalMath::percentageFromAmount($baseAmount, $commissionAmount);
    }

    public static function assertNonNegative(string $value, string $field): void
    {
        if (DecimalMath::isNegative($value)) {
            throw ValidationException::withMessages([
                $field => __('crm.commissions.validation.negative_value', ['field' => $field]),
            ]);
        }
    }

    public static function assertBaseAmount(string $baseAmount): void
    {
        self::assertNonNegative($baseAmount, 'base_amount');

        if (DecimalMath::isNegative($baseAmount)) {
            throw ValidationException::withMessages([
                'base_amount' => __('crm.commissions.validation.base_amount_negative'),
            ]);
        }
    }

    public static function exceedsMaxPercentage(string $percentage): bool
    {
        return DecimalMath::compare($percentage, self::maxPercentage(), DecimalMath::RATE_SCALE) === 1;
    }

    public static function assertPercentageWithinLimit(string $percentage, ?User $user = null, $commission = null): void
    {
        self::assertNonNegative($percentage, 'commission_percentage');

        if (! self::exceedsMaxPercentage($percentage)) {
            return;
        }

        $canOverride = $user !== null && (
            ($commission !== null && OpportunityCommissionAccess::canOverridePercentageLimit($user, $commission))
            || ($commission === null && $user->can('crm_commissions.override_percentage_limit'))
        );

        if ($canOverride) {
            return;
        }

        throw ValidationException::withMessages([
            'commission_percentage' => __('crm.commissions.validation.percentage_over_limit', [
                'max' => self::maxPercentage(),
            ]),
        ]);
    }

    public static function syncFromPercentage(string $baseAmount, string $percentage): array
    {
        self::assertBaseAmount($baseAmount);

        return [
            'commission_percentage' => DecimalMath::normalize($percentage, DecimalMath::RATE_SCALE),
            'commission_amount' => self::amountFromPercentage($baseAmount, $percentage),
            'last_manual_edit_field' => 'percentage',
        ];
    }

    public static function syncFromAmount(string $baseAmount, string $commissionAmount): array
    {
        self::assertBaseAmount($baseAmount);

        if (DecimalMath::isZero($baseAmount)) {
            throw ValidationException::withMessages([
                'commission_amount' => __('crm.commissions.validation.cannot_derive_percentage_from_zero_base'),
            ]);
        }

        self::assertNonNegative($commissionAmount, 'commission_amount');

        return [
            'commission_amount' => DecimalMath::normalize($commissionAmount),
            'commission_percentage' => self::percentageFromAmount($baseAmount, $commissionAmount),
            'last_manual_edit_field' => 'amount',
        ];
    }
}
