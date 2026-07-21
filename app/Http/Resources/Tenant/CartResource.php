<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subtotal = $this->items->sum(fn ($item) => $item->unitPrice() * $item->quantity);
        $shippingCost = (float) ($this->governorate?->shipping_cost ?? 0);

        return [
            'token' => $this->token,
            'subtotal' => $subtotal,
            'discount' => 0,
            'shipping_cost' => $shippingCost,
            'total' => max(0, $subtotal + $shippingCost),
            'governorate' => GovernorateResource::make($this->whenLoaded('governorate')),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
