<?php

namespace App\Enums\Crm;

enum CommissionPaymentEntryType: string
{
    case PAYMENT = 'payment';
    case REVERSAL = 'reversal';

    public function label(): string
    {
        return __('crm.enums.commission_payment_entry_type.'.$this->value);
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
