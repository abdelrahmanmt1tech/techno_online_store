<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'price' => $this->product->price,
                'sale_price' => $this->product->sale_price,
                'media' => $this->product->media->map(fn ($m) => [
                    'file' => asset('storage/'.$m->file),
                    'type' => $m->type,
                ]),
            ]),
            'variant' => $this->whenLoaded('variant', fn () => [
                'id' => $this->variant->id,
                'price' => $this->variant->price,
                'sale_price' => $this->variant->sale_price,
                'sku' => $this->variant->sku,
                'options' => $this->variant->options->map(fn ($o) => [
                    'value' => $o->value,
                    'variation_name' => $o->variation->name ?? null,
                ]),
            ]),
        ];
    }
}
