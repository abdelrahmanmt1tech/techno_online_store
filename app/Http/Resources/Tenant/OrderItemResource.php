<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'variant_options' => $this->variant_options,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->unit_price * $this->quantity,
        ];
    }
}
