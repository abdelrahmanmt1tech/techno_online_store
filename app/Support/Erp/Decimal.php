<?php

namespace App\Support\Erp;

use InvalidArgumentException;

/**
 * حساب عشري دقيق عبر BCMath — لا نستخدم float للأموال أو كميات ERP.
 */
final class Decimal
{
    public const SCALE = 4;

    public const MONEY_SCALE = 2;

    public static function of(string|int|float $value, int $scale = self::SCALE): string
    {
        if (is_float($value)) {
            // تحويل عبر string لتجنب تمثيل float الثنائي
            $value = sprintf('%.'.($scale + 4).'F', $value);
        }

        return bcadd((string) $value, '0', $scale);
    }

    public static function add(string|int $a, string|int $b, int $scale = self::SCALE): string
    {
        return bcadd(self::of($a, $scale), self::of($b, $scale), $scale);
    }

    public static function sub(string|int $a, string|int $b, int $scale = self::SCALE): string
    {
        return bcsub(self::of($a, $scale), self::of($b, $scale), $scale);
    }

    public static function mul(string|int $a, string|int $b, int $scale = self::SCALE): string
    {
        return bcmul(self::of($a, $scale), self::of($b, $scale), $scale);
    }

    public static function div(string|int $a, string|int $b, int $scale = self::SCALE): string
    {
        $divisor = self::of($b, $scale);
        if (bccomp($divisor, '0', $scale) === 0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        return bcdiv(self::of($a, $scale), $divisor, $scale);
    }

    public static function cmp(string|int $a, string|int $b, int $scale = self::SCALE): int
    {
        return bccomp(self::of($a, $scale), self::of($b, $scale), $scale);
    }

    public static function isZero(string|int $value, int $scale = self::SCALE): bool
    {
        return self::cmp($value, '0', $scale) === 0;
    }

    public static function isPositive(string|int $value, int $scale = self::SCALE): bool
    {
        return self::cmp($value, '0', $scale) > 0;
    }

    public static function isNegative(string|int $value, int $scale = self::SCALE): bool
    {
        return self::cmp($value, '0', $scale) < 0;
    }

    public static function min(string|int $a, string|int $b, int $scale = self::SCALE): string
    {
        return self::cmp($a, $b, $scale) <= 0 ? self::of($a, $scale) : self::of($b, $scale);
    }

    public static function money(string|int $value): string
    {
        return self::of($value, self::MONEY_SCALE);
    }

    /** هل القيمة عدد صحيح (مناسب لكمية متجر Integer)؟ */
    public static function isIntegerQuantity(string|int $value): bool
    {
        $normalized = self::of($value, self::SCALE);

        return self::cmp($normalized, (string) (int) $normalized, self::SCALE) === 0;
    }

    public static function toIntQuantity(string|int $value): int
    {
        if (! self::isIntegerQuantity($value)) {
            throw new InvalidArgumentException('Commerce quantity must be a whole number.');
        }

        return (int) self::of($value, 0);
    }
}
