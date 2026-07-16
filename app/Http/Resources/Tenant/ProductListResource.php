<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $discountPercent = $this->price > 0 && $this->sale_price !== null && $this->sale_price < $this->price
            ? round((($this->price - $this->sale_price) / $this->price) * 100)
            : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'discount_percent' => $discountPercent,
            'image' => $this->whenLoaded('media', fn () => $this->media->first() ? asset('storage/tenant'.tenant('id').'/'.$this->media->first()->file) : null),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])),
        ];
    }
}
