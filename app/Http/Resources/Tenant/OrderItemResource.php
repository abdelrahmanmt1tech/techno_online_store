<?php

namespace App\Http\Resources\Tenant;

use App\Helper\CurrencyHelper;
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
            'options' => $this->variant?->options->map(fn($o) => [
                'name' => $o->variation->name ?? null,
                'value' => $o->value,
            ]),
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'total_price' => $this->unit_price * $this->quantity,
            'currency' => CurrencyHelper::getCurrency(),
            'product_image' => $this->whenLoaded('product', function () {
                $media = $this->product->media->first();

                return $media
                    ? asset('storage/tenant' . tenant('id') . '/' . $media->file)
                    : null;
            }),
        ];
    }
}
