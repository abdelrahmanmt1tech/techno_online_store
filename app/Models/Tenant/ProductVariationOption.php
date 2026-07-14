<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariationOption extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'variation_id',
        'value',
        'color_code',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'variation_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_options',
            'option_id',
            'variant_id',
        );
    }
}
