<?php

namespace App\Enums\Crm;

enum CommissionAdjustmentDirection: string
{
    case INCREASE = 'increase';
    case DECREASE = 'decrease';

    public function label(): string
    {
        return __('crm.enums.commission_adjustment_direction.'.$this->value);
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
