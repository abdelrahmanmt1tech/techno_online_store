<?php

namespace App\Models\Tenant;

use App\Enums\Erp\ReturnDisposition;
use App\Enums\Erp\SaleItemSourceType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnItem extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'sales_return_id',
        'sale_item_id',
        'sales_invoice_item_id',
        'source_type',
        'disposition',
        'warehouse_id',
        'quantity',
        'unit_price',
        'unit_cost',
        'line_total',
        'cost_total',
        'stock_transaction_line_id',
        'commerce_quantity_adjustment_id',
    ];

    protected $casts = [
        'source_type' => SaleItemSourceType::class,
        'disposition' => ReturnDisposition::class,
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
        'cost_total' => 'decimal:4',
    ];

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function salesInvoiceItem(): BelongsTo
    {
        return $this->belongsTo(SalesInvoiceItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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
