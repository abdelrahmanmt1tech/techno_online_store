<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar
                ? asset('storage/tenant' . tenant('id') . '/avatars/' . $this->avatar)
                : null,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
        ];
    }
}
