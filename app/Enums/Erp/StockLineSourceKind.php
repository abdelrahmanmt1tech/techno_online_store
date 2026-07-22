<?php

namespace App\Enums\Erp;

enum StockLineSourceKind: string
{
    case Inventory = 'inventory';
    case Commerce = 'commerce';

    public function label(): string
    {
        return __('erp.stock_line_source_kinds.'.$this->value);
    }
}
