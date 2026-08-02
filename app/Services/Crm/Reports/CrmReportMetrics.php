<?php

namespace App\Services\Crm\Reports;

use App\Support\Money\DecimalMath;
use InvalidArgumentException;

final class CrmReportMetrics
{
    public const NOT_APPLICABLE = 'N/A';

    public static function displayValue(string $value): string
    {
        return $value === self::NOT_APPLICABLE
            ? __('crm.reports.common.not_applicable')
            : $value;
    }

    public static function displayPercent(string $value): string
    {
        return $value === self::NOT_APPLICABLE
            ? __('crm.reports.common.not_applicable')
            : $value.'%';
    }

    public static function conversionRate(int $won, int $closed): string
    {
        if ($closed === 0) {
            return '0.00';
        }

        return DecimalMath::div(bcmul((string) $won, '100', 2), (string) $closed, 2);
    }

    public static function divideOrNa(?string $numerator, ?string $denominator): string
    {
        if ($denominator === null || DecimalMath::isZero($denominator)) {
            return self::NOT_APPLICABLE;
        }

        try {
            return DecimalMath::div($numerator ?? DecimalMath::zero(), $denominator);
        } catch (InvalidArgumentException) {
            return self::NOT_APPLICABLE;
        }
    }

    public static function expectedRoiPercent(string $wonAgreedTotal, string $budget): string
    {
        if (DecimalMath::isZero($budget)) {
            return self::NOT_APPLICABLE;
        }

        $profit = DecimalMath::sub($wonAgreedTotal, $budget);

        return DecimalMath::mul(DecimalMath::div($profit, $budget), '100', 2);
    }

    public static function averagePerItem(int $total, int $itemCount, int $scale = 2): string
    {
        if ($itemCount === 0) {
            return '0.00';
        }

        return DecimalMath::div((string) $total, (string) $itemCount, $scale);
    }
}
