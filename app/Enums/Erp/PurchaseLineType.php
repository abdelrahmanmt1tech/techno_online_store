<?php

namespace App\Enums\Erp;

enum PurchaseLineType: string
{
    case Inventory = 'inventory';
    case Commerce = 'commerce';
    case Service = 'service';
    case Expense = 'expense';
    case Asset = 'asset';
    case Manual = 'manual';

    public function label(): string
    {
        return __('erp.purchase_line_types.'.$this->value);
    }

    public function affectsStock(): bool
    {
        return in_array($this, [self::Inventory, self::Commerce, self::Asset], true);
    }
}
