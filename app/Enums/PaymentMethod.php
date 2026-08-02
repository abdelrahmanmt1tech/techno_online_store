<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case BANK_TRANSFER = 'bank_transfer';
    case CHECK = 'check';
    case CREDIT_CARD = 'credit_card';

    public function label(): string
    {
        return match ($this) {
            self::CASH => __('crm.payment_methods.cash'),
            self::BANK_TRANSFER => __('crm.payment_methods.bank_transfer'),
            self::CHECK => __('crm.payment_methods.check'),
            self::CREDIT_CARD => __('crm.payment_methods.credit_card'),
        };
    }

    public static function options(): array
    {
        return array_combine(
            array_map(fn (self $option) => $option->value, self::cases()),
            array_map(fn (self $option) => $option->label(), self::cases()),
        );
    }
}
