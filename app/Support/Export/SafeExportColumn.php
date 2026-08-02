<?php

namespace App\Support\Export;

use Filament\Actions\Exports\ExportColumn;

/**
 * Factory for {@see ExportColumn} instances whose user-supplied text is sanitised against
 * CSV/Excel formula injection via {@see CsvFormulaGuard}. Use these for name/title/notes/
 * reference/reason columns so the mitigation lives in one place instead of every exporter.
 */
final class SafeExportColumn
{
    /**
     * A plain user-supplied string column (opportunity title, user name, reference, …).
     */
    public static function text(string $name, ?string $label = null): ExportColumn
    {
        return self::labelled($name, $label)
            ->formatStateUsing(fn ($state) => CsvFormulaGuard::escape(self::toString($state)));
    }

    /**
     * A translatable (JSON) name column (client, lead source, campaign, branch, stage, …).
     * Resolves the current-locale value, then sanitises it.
     */
    public static function translatable(string $name, ?string $label = null): ExportColumn
    {
        return self::labelled($name, $label)
            ->formatStateUsing(fn ($state) => CsvFormulaGuard::escape(self::localize($state)));
    }

    private static function labelled(string $name, ?string $label): ExportColumn
    {
        $column = ExportColumn::make($name);

        if ($label !== null) {
            $column->label($label);
        }

        return $column;
    }

    private static function toString(mixed $state): string
    {
        if (is_array($state)) {
            return self::localize($state);
        }

        return (string) ($state ?? '');
    }

    private static function localize(mixed $state): string
    {
        if (is_array($state)) {
            $value = $state[app()->getLocale()] ?? (reset($state) ?: '');

            return (string) $value;
        }

        return (string) ($state ?? '');
    }
}
