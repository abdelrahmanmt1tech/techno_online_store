<?php

namespace App\Enums\Pos;

enum CashMovementType: string
{
    case CashIn = 'cash_in';
    case CashOut = 'cash_out';
    case SafeDrop = 'safe_drop';
    case PayIn = 'pay_in';
    case PayOut = 'pay_out';
    case Opening = 'opening';
    case Closing = 'closing';

    public function label(): string
    {
        return __('commerce.cash_movement_types.'.$this->value);
    }
}
