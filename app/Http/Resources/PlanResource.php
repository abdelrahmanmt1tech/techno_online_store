<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $typeLabels = [
            'commission' => __('dashboard.type_commission'),
            'subscription' => match ($this->subscription_period) {
                'monthly' => __('dashboard.type_subscription_monthly'),
                'yearly' => __('dashboard.type_subscription_yearly'),
                default => __('dashboard.type_subscription'),
            },
        ];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $typeLabels[$this->type] ?? $this->type,
            'is_active' => $this->type === 'commission',
            'price' => $this->price,
            'currency' => $this->currency,
            // 'commission_per_order' => $this->commission_per_order,
            // 'subscription_period' => $this->subscription_period,
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
