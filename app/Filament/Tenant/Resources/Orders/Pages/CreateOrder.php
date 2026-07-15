<?php

namespace App\Filament\Tenant\Resources\Orders\Pages;

use App\Filament\Tenant\Resources\Orders\OrderResource;
use App\Models\Tenant\CouponUsage;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected ?array $itemsData = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $itemsData = $data['items_data'] ?? [];
        unset($data['items_data']);

        $subtotal = 0;

        foreach ($itemsData as &$item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                continue;
            }

            $variant = isset($item['product_variant_id'])
                ? ProductVariant::find($item['product_variant_id'])
                : null;

            $item['product_name'] = $product->name;
            $item['product_sku'] = $variant?->sku ?? $product->sku;
            $item['variant_options'] = $variant
                ? $variant->options->mapWithKeys(fn ($o) => [
                    $o->variation->name ?? 'Option' => $o->value,
                ])->toArray()
                : null;

            $item['total_price'] = $item['unit_price'] * $item['quantity'];
            $subtotal += $item['total_price'];
        }
        unset($item);

        $governorate = $data['governorate_id']
            ? Governorate::find($data['governorate_id'])
            : null;
        $data['governorate_name'] = $governorate?->name ?? '';
        $data['shipping_cost'] = $governorate?->shipping_cost ?? ($data['shipping_cost'] ?? 0);
        $data['subtotal'] = $subtotal;
        $data['discount'] = $data['discount'] ?? 0;
        $data['total'] = max(0, $subtotal - $data['discount'] + $data['shipping_cost']);

        $this->itemsData = $itemsData;

        return $data;
    }

    protected function afterCreate(): void
    {
        $itemsData = $this->itemsData ?? [];

        foreach ($itemsData as $item) {
            OrderItem::create([
                'order_id' => $this->record->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'product_name' => $item['product_name'],
                'product_sku' => $item['product_sku'] ?? null,
                'variant_options' => $item['variant_options'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);

            if ($item['product_variant_id']) {
                ProductVariant::where('id', $item['product_variant_id'])
                    ->decrement('quantity', $item['quantity']);
            } else {
                Product::where('id', $item['product_id'])
                    ->decrement('quantity', $item['quantity']);
            }
        }

        if ($this->record->coupon_id) {
            CouponUsage::create([
                'coupon_id' => $this->record->coupon_id,
                'order_id' => $this->record->id,
                'discount_amount' => $this->record->discount,
            ]);
        }

        $this->itemsData = [];
    }
}
