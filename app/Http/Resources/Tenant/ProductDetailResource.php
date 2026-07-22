<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstVariant = $this->variants?->first();

        $price = $firstVariant?->price ?? $this->price;
        $salePrice = $firstVariant?->sale_price ?? $firstVariant?->price ?? $this->sale_price;
        $quantity = $firstVariant?->quantity ?? $this->quantity;

        $discountPercent = $price > 0 && $salePrice !== null && $salePrice < $price
            ? round((($price - $salePrice) / $price) * 100)
            : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $price,
            'sale_price' => $salePrice,
            'discount_percent' => $discountPercent,
            'description' => $this->description,
            'quantity' => $quantity,
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($m) => asset('storage/tenant'.tenant('id').'/'.$m->file))->values()),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])),
            'variations' => $this->whenLoaded('variations', fn () => $this->variations->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'type' => $v->type,
                'options' => $v->options->map(fn ($o) => [
                    'id' => $o->id,
                    'value' => $o->value,
                    'color_code' => $o->color_code,
                ]),
            ])),
            'variants' => $this->whenLoaded('variants', fn () => $this->variants->map(fn ($v) => [
                'id' => $v->id,
                'price' => $v->price,
                'sale_price' => $v->sale_price ?? $v->price,
                'discount_percent' => $v->price > 0 && $v->sale_price !== null && $v->sale_price < $v->price
                    ? round((($v->price - $v->sale_price) / $v->price) * 100)
                    : 0,
                'quantity' => $v->quantity,
                'image' => $v->image ? asset('storage/tenant'.tenant('id').'/'.$v->image) : null,
                'options' => $v->options->map(fn ($o) => [
                    'id' => $o->id,
                    'value' => $o->value,
                    'variation_name' => $o->variation->name ?? null,
                ]),
            ])),
            'is_favorite' => $this->is_favorite ?? false,
        ];
    }
}
