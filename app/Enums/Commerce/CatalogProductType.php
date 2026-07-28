<?php

namespace App\Enums\Commerce;

enum CatalogProductType: string
{
    case InventoryItem = 'inventory_item';
    case Service = 'service';
    case Digital = 'digital';
    case Bundle = 'bundle';
    case RawMaterial = 'raw_material';
    case NonStockItem = 'non_stock_item';

    public function label(): string
    {
        return __('commerce.catalog_product_types.'.$this->value);
    }

    /**
     * Whether this catalog type should participate in ERP/FIFO stock by design.
     * Bundle: no qty deduction in this phase (design only).
     */
    public function tracksInventoryByDefault(): bool
    {
        return match ($this) {
            self::InventoryItem, self::RawMaterial => true,
            self::Service, self::Digital, self::Bundle, self::NonStockItem => false,
        };
    }

    /**
     * Maps to legacy storefront `products.type` (physical|digital).
     */
    public function legacyStoreType(): string
    {
        return $this === self::Digital ? 'digital' : 'physical';
    }

    public static function fromLegacyStoreType(string $type): self
    {
        return $type === 'digital' ? self::Digital : self::InventoryItem;
    }
}
