<?php

namespace App\Filament\Tenant\Resources\Orders\Pages;

use App\Filament\Tenant\Resources\Orders\OrderResource;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected ?array $itemsData = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items_data'] = $this->record->items->map(fn (OrderItem $item) => [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->itemsData = $data['items_data'] ?? [];
        unset($data['items_data']);

        $subtotal = 0;
        foreach ($this->itemsData as $item) {
            $subtotal += (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        }

        $data['subtotal'] = $subtotal;
        $data['total'] = max(0, $subtotal - ($data['discount'] ?? 0) + ($data['shipping_cost'] ?? 0));

        return $data;
    }

    protected function afterSave(): void
    {
        $oldItems = $this->record->items()->get();

        foreach ($oldItems as $oldItem) {
            if ($oldItem->product_variant_id) {
                ProductVariant::where('id', $oldItem->product_variant_id)
                    ->increment('quantity', $oldItem->quantity);
            } else {
                Product::where('id', $oldItem->product_id)
                    ->increment('quantity', $oldItem->quantity);
            }
        }

        $this->record->items()->delete();

        foreach ($this->itemsData as $item) {
            $product = Product::find($item['product_id']);
            if (! $product) {
                continue;
            }

            $variant = isset($item['product_variant_id'])
                ? ProductVariant::find($item['product_variant_id'])
                : null;

            OrderItem::create([
                'order_id' => $this->record->id,
                'product_id' => $item['product_id'],
                'product_variant_id' => $item['product_variant_id'] ?? null,
                'product_name' => $product->name,
                'product_sku' => $variant?->sku ?? $product->sku,
                'variant_options' => $variant
                    ? $variant->options->mapWithKeys(fn ($o) => [
                        $o->variation->name ?? 'Option' => $o->value,
                    ])->toArray()
                    : null,
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

        $this->itemsData = [];
    }
}
