<?php

namespace App\Models\Tenant;

use App\Enums\Erp\PurchaseLineType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnItem extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'purchase_return_id',
        'goods_receipt_item_id',
        'purchase_order_item_id',
        'line_type',
        'inventory_item_id',
        'product_id',
        'product_variant_id',
        'description_snapshot',
        'quantity',
        'unit_cost',
        'total_cost',
        'stock_transaction_line_id',
        'commerce_quantity_adjustment_id',
    ];

    protected $casts = [
        'line_type' => PurchaseLineType::class,
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:4',
    ];

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
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

    public function stockTransactionLine(): BelongsTo
    {
        return $this->belongsTo(StockTransactionLine::class);
    }

    public function commerceQuantityAdjustment(): BelongsTo
    {
        return $this->belongsTo(CommerceQuantityAdjustment::class);
    }
}
