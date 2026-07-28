<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use BelongsToTenantConnection;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_filterable',
        'is_visible_on_storefront',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_filterable' => 'boolean',
        'is_visible_on_storefront' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'attribute_product')
            ->withPivot(['attribute_value_id', 'value_text'])
            ->withTimestamps();
    }
}
