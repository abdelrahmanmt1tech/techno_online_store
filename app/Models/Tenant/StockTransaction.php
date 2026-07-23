<?php

namespace App\Models\Tenant;

use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockTransaction extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;

    protected $fillable = [
        'document_number',
        'transaction_type',
        'status',
        'branch_id',
        'source_warehouse_id',
        'destination_warehouse_id',
        'transaction_date',
        'reference_type',
        'reference_id',
        'notes',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
        'reversal_transaction_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_type' => StockTransactionType::class,
        'status' => DocumentStatus::class,
        'transaction_date' => 'date',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sourceWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'reversed_by');
    }

    public function reversalTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_transaction_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransactionLine::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
