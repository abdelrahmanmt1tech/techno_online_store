<?php

namespace App\Models\Tenant;

use App\Enums\Erp\MovementDirection;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StockMovement extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'stock_transaction_id',
        'stock_transaction_line_id',
        'warehouse_id',
        'inventory_item_id',
        'direction',
        'quantity',
        'unit_cost',
        'total_cost',
        'movement_date',
        'created_by',
    ];

    protected $casts = [
        'direction' => MovementDirection::class,
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'movement_date' => 'datetime',
    ];

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function stockTransactionLine(): BelongsTo
    {
        return $this->belongsTo(StockTransactionLine::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(StockLayerConsumption::class);
    }

    public function costLayer(): HasOne
    {
        return $this->hasOne(StockCostLayer::class);
    }
}
