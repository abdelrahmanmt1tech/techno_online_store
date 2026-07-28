<?php

namespace App\Services\Commerce;

use App\Enums\Commerce\CatalogProductType;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;

/**
 * Shared catalog helpers for Store / ERP / POS — no duplicated product models.
 */
final class CatalogService
{
    public function resolveCatalogType(Product $product): CatalogProductType
    {
        if ($product->catalog_type instanceof CatalogProductType) {
            return $product->catalog_type;
        }

        return CatalogProductType::fromLegacyStoreType((string) ($product->type ?? 'physical'));
    }

    public function tracksInventory(Product $product): bool
    {
        return $product->shouldTrackInventory();
    }

    /**
     * Bundle lines must not deduct stock in this phase — callers should skip.
     */
    public function allowsStockDeduction(Product $product): bool
    {
        $type = $this->resolveCatalogType($product);

        if ($type === CatalogProductType::Bundle) {
            return false;
        }

        return $type->tracksInventoryByDefault() && $product->track_stock;
    }

    public function findBySkuOrBarcode(?string $code): Product|ProductVariant|null
    {
        if ($code === null || $code === '') {
            return null;
        }

        $variant = ProductVariant::query()
            ->where(function ($q) use ($code) {
                $q->where('sku', $code)->orWhere('barcode', $code);
            })
            ->first();

        if ($variant) {
            return $variant;
        }

        return Product::query()
            ->where(function ($q) use ($code) {
                $q->where('sku', $code)->orWhere('barcode', $code);
            })
            ->first();
    }
}
