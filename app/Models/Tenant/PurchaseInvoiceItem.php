<?php

namespace App\Models\Tenant;

use App\Enums\Erp\PurchaseLineType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'purchase_invoice_id',
        'goods_receipt_item_id',
        'line_type',
        'description_snapshot',
        'sku_snapshot',
        'quantity',
        'unit_cost',
        'discount',
        'tax',
        'line_total',
    ];

    protected $casts = [
        'line_type' => PurchaseLineType::class,
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function goodsReceiptItem(): BelongsTo
    {
        return $this->belongsTo(GoodsReceiptItem::class);
    }
}
