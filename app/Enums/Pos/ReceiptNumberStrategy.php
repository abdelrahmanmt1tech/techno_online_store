<?php

namespace App\Enums\Pos;

enum ReceiptNumberStrategy: string
{
    /** Branch + Register + Date + daily sequence (default). */
    case BranchRegisterDate = 'branch_register_date';
    case PerRegister = 'per_register';
    case Global = 'global';

    public function label(): string
    {
        return __('commerce.receipt_number_strategies.'.$this->value);
    }
}
