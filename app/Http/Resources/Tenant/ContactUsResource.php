<?php

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactUsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this['contact_us_title'] ?? '',
            'description' => $this['contact_us_description'] ?? '',
            'image' => $this['contact_us_image']
                ? asset('storage/tenant'.tenant('id').'/'.$this['contact_us_image'])
                : null,
            'email' => $this['contact_us_email'] ?? '',
            'phone' => $this['contact_us_phone'] ?? '',
            'whatsapp' => $this['contact_us_whatsapp'] ?? '',
        ];
    }
}
