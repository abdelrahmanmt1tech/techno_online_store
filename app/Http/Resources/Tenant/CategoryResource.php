<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'seo' => $this->whenLoaded('seo', fn () => $this->seo ? [
                'meta_title' => $this->seo->meta_title,
                'meta_description' => $this->seo->meta_description,
                'keywords' => $this->seo->keywords
                    ? array_map('trim', explode(' ', $this->seo->keywords))
                    : [],
                'canonical_url' => $this->seo->canonical_url,
                'og_image' => $this->seo->og_image_url,
            ] : null),
        ];
    }
}
