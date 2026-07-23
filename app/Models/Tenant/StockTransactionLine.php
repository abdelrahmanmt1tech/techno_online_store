<?php

namespace App\Models\Tenant;

use App\Enums\Erp\StockLineSourceKind;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransactionLine extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'stock_transaction_id',
        'inventory_item_id',
        'source_kind',
        'quantity',
        'unit_cost',
        'total_cost',
        'product_id',
        'product_variant_id',
        'affects_commerce_quantity',
        'commerce_quantity_delta',
        'notes',
    ];

    protected $casts = [
        'source_kind' => StockLineSourceKind::class,
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'affects_commerce_quantity' => 'boolean',
        'commerce_quantity_delta' => 'decimal:4',
    ];

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
