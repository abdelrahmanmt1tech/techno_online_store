<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Plan extends Model
{
    use HasFactory, HasTranslations;

    public array $translatable = ['name', 'title', 'description'];

    protected $fillable = [
        'name',
        'title',
        'description',
        'type',
        'price',
        'currency',
        'commission_per_order',
        'subscription_period',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'commission_per_order' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }
}
