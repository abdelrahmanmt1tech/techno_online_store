<?php

namespace App\Enums\Erp;

enum CostingMethod: string
{
    case Fifo = 'fifo';

    public function label(): string
    {
        return __('erp.costing_methods.'.$this->value);
    }
}
