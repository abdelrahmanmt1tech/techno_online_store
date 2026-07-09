<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'price',
        'sale_price',
        'expense',
        'order',
        'description',
        'quantity',
        'track_stock',
        'disable_orders_for_no_stock',
        'type',
        'link_if_type_digital',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'expense' => 'decimal:2',
        'track_stock' => 'boolean',
        'disable_orders_for_no_stock' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ProductCode::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}
