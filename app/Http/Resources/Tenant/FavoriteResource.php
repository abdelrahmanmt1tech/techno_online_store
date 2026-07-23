<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $firstVariant = $this->variants?->first();

        $price = $firstVariant?->price ?? $this->price;
        $salePrice = $firstVariant?->sale_price ?? $firstVariant?->price ?? $this->sale_price;

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
            'image' => $this->whenLoaded('media', fn () => $this->media->first() ? asset('storage/tenant'.tenant('id').'/'.$this->media->first()->file) : null),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
