<?php

namespace App\Models\Tenant;

use App\Enums\Erp\SaleItemSourceType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'sale_id',
        'source_type',
        'inventory_item_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'description_snapshot',
        'sku_snapshot',
        'variation_snapshot',
        'unit_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'discount',
        'tax',
        'line_total',
        'cost_total',
        'profit_total',
        'invoiced_quantity',
        'returned_quantity',
        'stock_transaction_line_id',
        'commerce_quantity_adjustment_id',
        'notes',
    ];

    protected $casts = [
        'source_type' => SaleItemSourceType::class,
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',
        'cost_total' => 'decimal:4',
        'profit_total' => 'decimal:2',
        'invoiced_quantity' => 'decimal:4',
        'returned_quantity' => 'decimal:4',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }

    public function stockTransactionLine(): BelongsTo
    {
        return $this->belongsTo(StockTransactionLine::class);
    }

    public function commerceQuantityAdjustment(): BelongsTo
    {
        return $this->belongsTo(CommerceQuantityAdjustment::class);
    }
}
