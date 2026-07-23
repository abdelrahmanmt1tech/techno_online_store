<?php

namespace App\Models\Tenant;

use App\Enums\Erp\PurchaseLineType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'purchase_order_id',
        'line_type',
        'inventory_item_id',
        'product_id',
        'product_variant_id',
        'description',
        'sku_snapshot',
        'unit_id',
        'quantity',
        'received_quantity',
        'returned_quantity',
        'unit_cost',
        'discount',
        'tax',
        'line_total',
        'notes',
    ];

    protected $casts = [
        'line_type' => PurchaseLineType::class,
        'quantity' => 'decimal:4',
        'received_quantity' => 'decimal:4',
        'returned_quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }
}
