<?php

namespace App\Models\Tenant;

use App\Enums\FinancialPeriodStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialPeriod extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
        'status',
        'is_current',
        'parent_period_id',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => FinancialPeriodStatus::class,
            'is_current' => 'boolean',
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function parentPeriod(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_period_id');
    }

    public function childPeriods(): HasMany
    {
        return $this->hasMany(self::class, 'parent_period_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'reopened_by');
    }

    public function operations(): HasMany
    {
        return $this->hasMany(Operation::class, 'financial_period_id');
    }

    public function closings(): HasMany
    {
        return $this->hasMany(PeriodClosing::class, 'financial_period_id');
    }

    public function transfersFrom(): HasMany
    {
        return $this->hasMany(PeriodTransfer::class, 'from_period_id');
    }

    public function transfersTo(): HasMany
    {
        return $this->hasMany(PeriodTransfer::class, 'to_period_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(AccountPeriodBalance::class, 'financial_period_id');
    }

    public function isOpen(): bool
    {
        return $this->status === FinancialPeriodStatus::OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === FinancialPeriodStatus::CLOSED;
    }

    public function canBeClosed(): bool
    {
        return in_array($this->status, [FinancialPeriodStatus::OPEN, FinancialPeriodStatus::CLOSING], true);
    }

    public function canBeReopened(): bool
    {
        return in_array($this->status, [FinancialPeriodStatus::CLOSED, FinancialPeriodStatus::ARCHIVED], true);
    }

    public function containsDate(\DateTimeInterface|string $date): bool
    {
        $date = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date;

        return $date >= $this->start_date?->format('Y-m-d')
            && $date <= $this->end_date?->format('Y-m-d');
    }
}
