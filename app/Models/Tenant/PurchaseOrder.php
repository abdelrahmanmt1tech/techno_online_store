<?php

namespace App\Models\Tenant;

use App\Enums\Erp\PurchaseOrderStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;

    protected $fillable = [
        'document_number',
        'supplier_id',
        'branch_id',
        'target_warehouse_id',
        'order_date',
        'expected_date',
        'status',
        'currency_code',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'notes',
        'approved_at',
        'approved_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'status' => PurchaseOrderStatus::class,
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function targetWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'target_warehouse_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function goodsReceipts(): HasMany
    {
        return $this->hasMany(GoodsReceipt::class);
    }
}
