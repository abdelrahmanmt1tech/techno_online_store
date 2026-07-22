<?php

namespace App\Enums\Erp;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case Card = 'card';
    case Wallet = 'wallet';
    case Online = 'online';
    case Other = 'other';

    public function label(): string
    {
        return __('erp.payment_methods.'.$this->value);
    }
}
