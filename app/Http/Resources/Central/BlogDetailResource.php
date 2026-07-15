<?php

namespace App\Http\Resources\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlogDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'published_at' => $this->published_at?->toIso8601String(),
            'views_count' => $this->views_count,
            'categories' => BlogCategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'content' => $this->content,
            'seo' => $this->whenLoaded('seo', fn () => SeoResource::make($this->seo)),
            'faqs' => FaqResource::collection(
                $this->whenLoaded('faqs', fn () => $this->faqs->where('is_active', true))
            ),
        ];
    }
}
