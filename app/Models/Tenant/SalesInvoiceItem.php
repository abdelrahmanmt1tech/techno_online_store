<?php

namespace App\Models\Tenant;

use App\Enums\Erp\SaleItemSourceType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceItem extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'sales_invoice_id',
        'sale_item_id',
        'source_type',
        'description_snapshot',
        'sku_snapshot',
        'variation_snapshot',
        'unit_id',
        'quantity',
        'unit_price',
        'discount',
        'tax',
        'line_total',
    ];

    protected $casts = [
        'source_type' => SaleItemSourceType::class,
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasure::class, 'unit_id');
    }
}
