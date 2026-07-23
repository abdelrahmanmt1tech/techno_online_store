<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,
            'is_featured' => $this->is_featured,
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar
                    ? asset('storage/tenant' . tenant('id') . '/avatars/' . $this->user->avatar)
                    : null,
            ],
            'reviewable' => [
                'type' => class_basename($this->reviewable_type),
                'id' => $this->reviewable->id,
                'name' => $this->reviewable->name ?? null,
                'slug' => $this->reviewable->slug ?? null,
            ],
        ];
    }
}
