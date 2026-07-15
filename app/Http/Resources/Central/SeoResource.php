<?php

namespace App\Http\Resources\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'keywords' => $this->keywords
                ? array_map('trim', explode(' ', $this->keywords))
                : [],
            'canonical_url' => $this->canonical_url,
            'og_image' => $this->og_image_url,
        ];
    }
}
