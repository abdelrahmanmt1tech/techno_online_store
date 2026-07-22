<?php

namespace App\Enums\Erp;

enum WarehouseType: string
{
    case Regular = 'regular';
    case Central = 'central';
    case Returns = 'returns';
    case Damaged = 'damaged';
    case Other = 'other';

    public function label(): string
    {
        return __('erp.warehouse_types.'.$this->value);
    }
}
