<?php

namespace App\Models\Tenant;

use App\Enums\Erp\CostLayerStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockCostLayer extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'warehouse_id',
        'inventory_item_id',
        'stock_movement_id',
        'source_type',
        'source_id',
        'received_at',
        'original_quantity',
        'remaining_quantity',
        'unit_cost',
        'total_cost',
        'status',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'original_quantity' => 'decimal:4',
        'remaining_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'status' => CostLayerStatus::class,
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(StockLayerConsumption::class);
    }
}
