<?php

namespace App\Http\Resources\Central;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        $prices = $this->prices->when(
            $request->filled('country_id'),
            fn ($prices) => $prices->where('country_id', (int) $request->country_id)
        );

        if ($prices->isEmpty()) {
            $prices = $this->prices->where('is_default', true);
        }

        return [
            'id' => $this->id,
            'module' => $this->module,
            'is_full_package' => $this->is_full_package,
            'name' => $this->name,
            'desc' => $this->desc ?: null,
            'image' => $this->image ? asset('storage/'.$this->image) : null,
            'trials_duration' => $this->trials_duration,
            'sort' => $this->sort,
            'is_active' => $this->is_active,
            'prices' => $prices
                ->sortBy('country_id')
                ->map(fn ($price) => new PackagePriceResource($price))
                ->values(),
        ];
    }
}
