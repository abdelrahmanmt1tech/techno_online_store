<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'product_id',
        'price',
        'sale_price',
        'expense',
        'quantity',
        'sku',
        'barcode',
        'weight',
        'length',
        'width',
        'height',
        'image',
        'meta',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'expense' => 'decimal:2',
        'quantity' => 'integer',
        'weight' => 'decimal:4',
        'length' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariationOption::class,
            'product_variant_options',
            'variant_id',
            'option_id',
        );
    }
}
