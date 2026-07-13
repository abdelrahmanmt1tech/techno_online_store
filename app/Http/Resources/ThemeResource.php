<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'preview_url' => $this->preview_url,
            'is_free' => $this->is_free,
            'price' => $this->price,
            'featured' => $this->featured,
            'downloads_count' => $this->downloads_count,
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
        ];
    }
}
