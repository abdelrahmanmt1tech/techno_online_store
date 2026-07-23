<?php

namespace App\Models\Tenant;

use App\Enums\Erp\DocumentStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;

    protected $fillable = [
        'document_number',
        'sale_id',
        'sales_invoice_id',
        'branch_id',
        'return_date',
        'status',
        'reason',
        'notes',
        'stock_transaction_id',
        'posted_at',
        'posted_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'return_date' => 'date',
        'status' => DocumentStatus::class,
        'posted_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function stockTransaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'posted_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
