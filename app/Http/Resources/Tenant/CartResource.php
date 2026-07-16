<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping_cost' => $this->shipping_cost,
            'total' => $this->total,
            'status' => $this->status,
            'governorate' => $this->whenLoaded('governorate', fn () => [
                'id' => $this->governorate->id,
                'name' => $this->governorate->name,
                'shipping_cost' => $this->governorate->shipping_cost,
            ]),
            'coupon' => $this->whenLoaded('coupon', fn () => [
                'code' => $this->coupon->code,
                'type' => $this->coupon->type,
                'value' => $this->coupon->value,
            ]),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
