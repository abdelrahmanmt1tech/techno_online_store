<?php

namespace App\Enums\Erp;

enum InventoryItemType: string
{
    case FinishedGood = 'finished_good';
    case RawMaterial = 'raw_material';
    case Consumable = 'consumable';
    case Packaging = 'packaging';
    case SparePart = 'spare_part';
    case Asset = 'asset';
    case Service = 'service';
    case Other = 'other';

    public function label(): string
    {
        return __('erp.item_types.'.$this->value);
    }

    public function tracksStockByDefault(): bool
    {
        return $this !== self::Service;
    }
}
