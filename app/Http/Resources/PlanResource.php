<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'price' => $this->price,
            'currency' => $this->currency,
            'commission_per_order' => $this->commission_per_order,
            'subscription_period' => $this->subscription_period,
            'features' => $this->features
                ->sortBy('order')
                ->map(fn ($feature) => [
                    'id' => $feature->id,
                    'name' => $feature->name,
                    'is_active' => $feature->is_active,
                ])->values(),
        ];
    }
}
