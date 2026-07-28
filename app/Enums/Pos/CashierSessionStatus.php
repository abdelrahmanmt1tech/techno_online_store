<?php

namespace App\Enums\Pos;

enum CashierSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return __('commerce.session_statuses.'.$this->value);
    }
}
