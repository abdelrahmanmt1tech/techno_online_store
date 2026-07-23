<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLayerConsumption extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'stock_movement_id',
        'stock_cost_layer_id',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function stockMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function stockCostLayer(): BelongsTo
    {
        return $this->belongsTo(StockCostLayer::class);
    }
}
