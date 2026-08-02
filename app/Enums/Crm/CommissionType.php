<?php

namespace App\Enums\Crm;

enum CommissionType: string
{
    case SALES = 'sales';
    case REFERRAL = 'referral';
    case BONUS = 'bonus';
    case OVERRIDE = 'override';
    case ADJUSTMENT = 'adjustment';

    public function label(): string
    {
        return __('crm.enums.commission_type.'.$this->value);
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
