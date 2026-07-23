<?php

namespace App\Filament\Tenant\Support\Erp;

use BackedEnum;

final class ErpEnumOptions
{
    /**
     * @param  class-string<BackedEnum>  $enumClass
     * @return array<string, string>
     */
    public static function options(string $enumClass): array
    {
        $options = [];

        foreach ($enumClass::cases() as $case) {
            $options[$case->value] = method_exists($case, 'label')
                ? $case->label()
                : __('erp.enums.'.$enumClass.'.'.$case->value);
        }

        return $options;
    }

    /**
     * @param  list<BackedEnum>  $cases
     * @return array<string, string>
     */
    public static function fromCases(array $cases): array
    {
        $options = [];

        foreach ($cases as $case) {
            $options[$case->value] = method_exists($case, 'label')
                ? $case->label()
                : $case->value;
        }

        return $options;
    }
}
