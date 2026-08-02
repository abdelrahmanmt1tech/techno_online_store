<?php

namespace App\Models\Tenant;

use App\Enums\Crm\CommissionPaymentEntryType;
use App\Support\Crm\CrmBranchVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class CommissionPayment extends Model
{
    use Concerns\BelongsToTenantConnection;

    public const UPDATED_AT = null;

    protected $fillable = [
        'opportunity_commission_id',
        'commission_payment_cycle_id',
        'user_id',
        'branch_id',
        'entry_type',
        'amount',
        'commission_amount_snapshot',
        'paid_amount_before',
        'paid_amount_after',
        'remaining_amount_after',
        'payment_method',
        'reference_number',
        'executed_at',
        'executed_by',
        'reverses_payment_id',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => CommissionPaymentEntryType::class,
            'amount' => 'decimal:2',
            'commission_amount_snapshot' => 'decimal:2',
            'paid_amount_before' => 'decimal:2',
            'paid_amount_after' => 'decimal:2',
            'remaining_amount_after' => 'decimal:2',
            'executed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Commission payment ledger entries are immutable and cannot be updated.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Commission payment ledger entries cannot be deleted. Use a reversal entry instead.');
        });
    }

    public function opportunityCommission(): BelongsTo
    {
        return $this->belongsTo(OpportunityCommission::class);
    }

    public function commissionPaymentCycle(): BelongsTo
    {
        return $this->belongsTo(CommissionPaymentCycle::class);
    }

    public function user(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function executedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'executed_by');
    }

    public function reversesPayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_payment_id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reverses_payment_id');
    }

    public function scopeVisibleToUser(Builder $query, User $user): Builder
    {
        return CrmBranchVisibility::applyPaymentScope($query, $user);
    }
}
