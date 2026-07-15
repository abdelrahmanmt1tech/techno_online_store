<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => $this->price,
            'sale_price' => $this->sale_price,
            'description' => $this->description,
            'type' => $this->type,
            'track_stock' => $this->track_stock,
            'quantity' => $this->quantity,
            'media' => $this->whenLoaded('media', fn () => $this->media->map(fn ($m) => [
                'id' => $m->id,
                'file' => asset('storage/'.$m->file),
                'type' => $m->type,
            ])),
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
                'sale_price' => $v->sale_price,
                'quantity' => $v->quantity,
                'sku' => $v->sku,
                'image' => $v->image ? asset('storage/'.$v->image) : null,
                'options' => $v->options->map(fn ($o) => [
                    'id' => $o->id,
                    'value' => $o->value,
                    'variation_name' => $o->variation->name ?? null,
                ]),
            ])),
        ];
    }
}
