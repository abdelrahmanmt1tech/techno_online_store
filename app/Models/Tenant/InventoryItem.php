<?php

namespace App\Models\Tenant;

use App\Enums\Erp\CostingMethod;
use App\Enums\Erp\InventoryItemType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'barcode',
        'item_type',
        'unit_id',
        'costing_method',
        'track_stock',
        'default_purchase_cost',
        'default_sale_price',
        'minimum_stock',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'item_type' => InventoryItemType::class,
        'costing_method' => CostingMethod::class,
        'track_stock' => 'boolean',
        'default_purchase_cost' => 'decimal:4',
        'default_sale_price' => 'decimal:2',
        'minimum_stock' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function commerceLink(): HasOne
    {
        return $this->hasOne(InventoryItemCommerceLink::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }
}
