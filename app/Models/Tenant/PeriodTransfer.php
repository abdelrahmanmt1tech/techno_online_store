<?php

namespace App\Models\Tenant;

use App\Enums\PeriodTransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodTransfer extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'from_period_id',
        'to_period_id',
        'opening_operation_id',
        'status',
        'transferred_at',
        'transferred_by',
        'reversed_at',
        'reversed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PeriodTransferStatus::class,
            'transferred_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function fromPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'from_period_id');
    }

    public function toPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'to_period_id');
    }

    public function openingOperation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'opening_operation_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'transferred_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'reversed_by');
    }
}
