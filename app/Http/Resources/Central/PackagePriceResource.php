<?php

namespace App\Http\Resources\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackagePriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'country_id' => $this->country_id,
            'country_name' => $this->country?->getTranslation('name', $locale),
            'currency_id' => $this->currency_id,
            'currency_code' => $this->currency?->code,
            'currency_symbol' => $this->currency?->symbol,
            'price' => (float) $this->price,
            'duration' => $this->duration,
            'duration_type' => $this->duration_type,
        ];
    }
}
