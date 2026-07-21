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
            'name' => $this->product->name,
            'image' => $this->whenLoaded('product', fn () => $this->product->media->first()
                ? asset('storage/tenant'.tenant('id').'/'.$this->product->media->first()->file)
                : null),

            'quantity' => $this->quantity,
            'price' => $this->variant?->price,
            'sale_price' => $this->variant?->sale_price ?? $this->variant?->price,
            'options' => $this->variant?->options->map(fn($o) => [
                'name' => $o->variation->name ?? null,
                'value' => $o->value,
            ]),


        ];
    }
}
