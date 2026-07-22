<?php

namespace App\Enums\Erp;

enum SaleItemSourceType: string
{
    case Inventory = 'inventory';
    case Commerce = 'commerce';
    case Manual = 'manual';

    public function label(): string
    {
        return __('erp.sale_item_source_types.'.$this->value);
    }
}
