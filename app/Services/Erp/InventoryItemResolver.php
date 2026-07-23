<?php

namespace App\Services\Erp;

use App\Enums\Erp\CommerceSourceType;
use App\Enums\Erp\InventoryItemType;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\InventoryItemCommerceLink;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\UnitOfMeasure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * إنشاء/جلب Inventory Item مرتبط بمنتج أو متغير متجر.
 */
final class InventoryItemResolver
{
    public function resolveOrCreateFromCommerce(
        ?int $productId,
        ?int $productVariantId,
        ?int $unitId = null,
    ): InventoryItem {
        if ($productVariantId) {
            return $this->forVariant($productVariantId, $unitId);
        }

        if ($productId) {
            return $this->forProduct($productId, $unitId);
        }

        throw ValidationException::withMessages([
            'commerce' => __('erp.validation.commerce_source_required'),
        ]);
    }

    public function forProduct(int $productId, ?int $unitId = null): InventoryItem
    {
        $link = InventoryItemCommerceLink::query()
            ->where('source_type', CommerceSourceType::Product->value)
            ->where('source_id', $productId)
            ->first();

        if ($link) {
            return $link->inventoryItem;
        }

        /** @var Product $product */
        $product = Product::query()->findOrFail($productId);

        if ($product->variants()->exists()) {
            throw ValidationException::withMessages([
                'product_id' => __('erp.validation.product_has_variants_use_variant'),
            ]);
        }

        $item = InventoryItem::query()->create([
            'name' => $product->name,
            'sku' => $product->sku,
            'item_type' => InventoryItemType::FinishedGood->value,
            'unit_id' => $unitId ?? $this->defaultPieceUnitId(),
            'costing_method' => 'fifo',
            'track_stock' => true,
            'default_purchase_cost' => $product->expense,
            'default_sale_price' => $product->sale_price ?? $product->price,
            'is_active' => true,
            'created_by' => Auth::guard('tenant')->id(),
        ]);

        InventoryItemCommerceLink::query()->create([
            'inventory_item_id' => $item->id,
            'source_type' => CommerceSourceType::Product->value,
            'source_id' => $productId,
        ]);

        return $item;
    }

    public function forVariant(int $variantId, ?int $unitId = null): InventoryItem
    {
        $link = InventoryItemCommerceLink::query()
            ->where('source_type', CommerceSourceType::ProductVariant->value)
            ->where('source_id', $variantId)
            ->first();

        if ($link) {
            return $link->inventoryItem;
        }

        /** @var ProductVariant $variant */
        $variant = ProductVariant::query()->with(['product', 'options'])->findOrFail($variantId);
        $label = $this->variantLabel($variant);

        $item = InventoryItem::query()->create([
            'name' => $label,
            'sku' => $variant->sku,
            'item_type' => InventoryItemType::FinishedGood->value,
            'unit_id' => $unitId ?? $this->defaultPieceUnitId(),
            'costing_method' => 'fifo',
            'track_stock' => true,
            'default_purchase_cost' => $variant->expense,
            'default_sale_price' => $variant->sale_price ?? $variant->price,
            'is_active' => true,
            'created_by' => Auth::guard('tenant')->id(),
        ]);

        InventoryItemCommerceLink::query()->create([
            'inventory_item_id' => $item->id,
            'source_type' => CommerceSourceType::ProductVariant->value,
            'source_id' => $variantId,
        ]);

        return $item;
    }

    public function variantLabel(ProductVariant $variant): string
    {
        $parts = [$variant->product?->name ?? 'Product'];
        foreach ($variant->options as $option) {
            $attr = $option->variation?->name ?? '';
            $parts[] = trim($attr.': '.$option->value, ': ');
        }
        if ($variant->sku) {
            $parts[] = $variant->sku;
        }

        return implode(' — ', array_filter($parts));
    }

    private function defaultPieceUnitId(): int
    {
        $unit = UnitOfMeasure::query()->where('code', 'PCS')->first();
        if ($unit) {
            return $unit->id;
        }

        $unit = UnitOfMeasure::query()->create([
            'name' => 'Piece',
            'code' => 'PCS',
            'symbol' => 'pcs',
            'allows_decimal' => false,
            'precision' => 0,
            'is_active' => true,
        ]);

        return $unit->id;
    }
}
