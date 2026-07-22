<?php

namespace App\Models\Tenant;

use App\Enums\Erp\SaleSourceType;
use App\Enums\Erp\SaleStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;

    protected $fillable = [
        'document_number',
        'source_type',
        'order_id',
        'customer_id',
        'branch_id',
        'sale_date',
        'status',
        'currency_code',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'cost_total',
        'profit_total',
        'notes',
        'confirmed_at',
        'confirmed_by',
        'reversed_at',
        'reversed_by',
        'stock_transaction_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'source_type' => SaleSourceType::class,
        'sale_date' => 'date',
        'status' => SaleStatus::class,
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'cost_total' => 'decimal:4',
        'profit_total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'confirmed_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'reversed_by');
    }

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }
}
