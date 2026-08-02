<?php

namespace App\Support\Export;

/**
 * Central mitigation for CSV / Excel / LibreOffice formula (a.k.a. CSV) injection.
 *
 * A cell whose (leading-whitespace-stripped) value begins with one of = + - @, or that begins
 * with a tab / carriage-return / line-feed control character, can be interpreted as a formula by
 * spreadsheet software. We neutralise it by prefixing a single quote, which forces the value to be
 * treated as literal text while remaining human-readable.
 *
 * Only apply this to user-supplied TEXT values (names, titles, notes, references, reasons).
 * Do NOT apply it to real numeric/monetary values or dates — those are produced by the system,
 * not the user, and prefixing them would corrupt the data (e.g. a negative amount "-400.00").
 */
final class CsvFormulaGuard
{
    /** @var list<string> */
    private const FORMULA_TRIGGERS = ['=', '+', '-', '@'];

    /** @var list<string> */
    private const CONTROL_PREFIXES = ["\t", "\r", "\n"];

    public static function escape(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (self::isDangerous($value)) {
            return "'".$value;
        }

        return $value;
    }

    public static function isDangerous(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        // Leading control characters used to smuggle a formula past naive checks.
        if (in_array($value[0], self::CONTROL_PREFIXES, true)) {
            return true;
        }

        // First non-whitespace character is a formula trigger.
        $trimmed = ltrim($value);

        return $trimmed !== '' && in_array($trimmed[0], self::FORMULA_TRIGGERS, true);
    }
}
