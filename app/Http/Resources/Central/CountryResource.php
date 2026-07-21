<?php

namespace App\Http\Resources\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country_code' => $this->country_code,
            // 'currency_name' => $this->currency_name,
            // 'currency_symbol' => $this->currency_symbol,
            'currency_id' => $this->whenLoaded('currency', fn () => $this->currency->id),
            'phone_code' => $this->phone_code,
            'icon' => $this->icon ? asset('storage/'.$this->icon) : null,
            'locale' => $this->locale,
        ];
    }
}
