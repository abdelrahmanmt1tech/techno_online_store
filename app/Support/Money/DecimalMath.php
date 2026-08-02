<?php

namespace App\Support\Money;

use InvalidArgumentException;

final class DecimalMath
{
    public const MONEY_SCALE = 2;

    public const RATE_SCALE = 4;

    public static function normalize(string|int|float|null $value, int $scale = self::MONEY_SCALE): string
    {
        if ($value === null || $value === '') {
            return self::zero($scale);
        }

        if (is_float($value)) {
            throw new InvalidArgumentException('Float values are not allowed for money calculations.');
        }

        if (! is_string($value) && ! is_int($value)) {
            throw new InvalidArgumentException('Only decimal strings or integers are allowed for money calculations.');
        }

        return bcadd((string) $value, '0', $scale);
    }

    public static function zero(int $scale = self::MONEY_SCALE): string
    {
        return bcadd('0', '0', $scale);
    }

    public static function add(string|int|float|null $left, string|int|float|null $right, int $scale = self::MONEY_SCALE): string
    {
        return bcadd(self::normalize($left, $scale), self::normalize($right, $scale), $scale);
    }

    public static function sub(string|int|float|null $left, string|int|float|null $right, int $scale = self::MONEY_SCALE): string
    {
        return bcsub(self::normalize($left, $scale), self::normalize($right, $scale), $scale);
    }

    public static function mul(string|int|float|null $left, string|int|float|null $right, int $scale = self::MONEY_SCALE): string
    {
        return bcmul(self::normalize($left, $scale), self::normalize($right, $scale), $scale);
    }

    public static function div(string|int|float|null $left, string|int|float|null $right, int $scale = self::MONEY_SCALE): string
    {
        $divisor = self::normalize($right, $scale);

        if (bccomp($divisor, self::zero($scale), $scale) === 0) {
            throw new InvalidArgumentException('Division by zero is not allowed.');
        }

        return bcdiv(self::normalize($left, $scale), $divisor, $scale);
    }

    public static function compare(string|int|float|null $left, string|int|float|null $right, int $scale = self::MONEY_SCALE): int
    {
        return bccomp(self::normalize($left, $scale), self::normalize($right, $scale), $scale);
    }

    public static function isZero(string|int|float|null $value, int $scale = self::MONEY_SCALE): bool
    {
        return self::compare($value, self::zero($scale), $scale) === 0;
    }

    public static function isNegative(string|int|float|null $value, int $scale = self::MONEY_SCALE): bool
    {
        return self::compare($value, self::zero($scale), $scale) === -1;
    }

    public static function isPositive(string|int|float|null $value, int $scale = self::MONEY_SCALE): bool
    {
        return self::compare($value, self::zero($scale), $scale) === 1;
    }

    public static function percentOfAmount(string|int|float|null $amount, string|int|float|null $percentage, int $scale = self::MONEY_SCALE): string
    {
        $intermediate = bcmul(
            self::normalize($amount, $scale),
            self::normalize($percentage, self::RATE_SCALE),
            self::RATE_SCALE,
        );

        return bcdiv($intermediate, '100', $scale);
    }

    public static function percentageFromAmount(string|int|float|null $amount, string|int|float|null $commissionAmount, int $scale = self::RATE_SCALE): string
    {
        $base = self::normalize($amount, self::MONEY_SCALE);

        if (self::isZero($base, self::MONEY_SCALE)) {
            return self::zero($scale);
        }

        $intermediate = bcmul(
            self::normalize($commissionAmount, self::MONEY_SCALE),
            '100',
            self::RATE_SCALE,
        );

        return bcdiv($intermediate, $base, $scale);
    }

    public static function remaining(string|int|float|null $commissionAmount, string|int|float|null $paidAmount, int $scale = self::MONEY_SCALE): string
    {
        return self::sub($commissionAmount, $paidAmount, $scale);
    }
}
