<?php

namespace App\Http\Resources\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'module' => $this->module,
            'is_full_package' => $this->is_full_package,
            'name' => $this->getTranslation('name', $locale),
            'desc' => $this->getTranslation('desc', $locale) ?: null,
            'trials_duration' => $this->trials_duration,
            'sort' => $this->sort,
            'is_active' => $this->is_active,
            'prices' => $this->prices
                ->when(
                    $request->filled('country_id'),
                    fn ($prices) => $prices->where('country_id', (int) $request->country_id)
                )
                ->sortBy([
                    ['country_id', 'asc'],
                    ['duration', 'asc'],
                ])
                ->map(fn ($price) => new PackagePriceResource($price))
                ->values(),
        ];
    }
}
