<?php

namespace App\Models\Tenant;

use App\Enums\Crm\CommissionAdjustmentDirection;
use App\Enums\Crm\CommissionAdjustmentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class OpportunityCommissionAdjustment extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'opportunity_commission_id',
        'direction',
        'amount',
        'reason',
        'status',
        'balance_before',
        'balance_after',
        'created_by',
        'approved_by',
        'rejected_by',
        'rejection_reason',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => CommissionAdjustmentDirection::class,
            'status' => CommissionAdjustmentStatus::class,
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (OpportunityCommissionAdjustment $adjustment): void {
            if ($adjustment->getRawOriginal('status') === CommissionAdjustmentStatus::APPROVED->value) {
                throw new RuntimeException('Approved commission adjustments are immutable and cannot be updated.');
            }
        });

        static::deleting(function (OpportunityCommissionAdjustment $adjustment): void {
            if ($adjustment->status === CommissionAdjustmentStatus::APPROVED) {
                throw new RuntimeException('Approved commission adjustments cannot be deleted. Create a reversing adjustment instead.');
            }
        });
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(OpportunityCommission::class, 'opportunity_commission_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'rejected_by');
    }

    /**
     * @param  Builder<OpportunityCommissionAdjustment>  $query
     * @return Builder<OpportunityCommissionAdjustment>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', CommissionAdjustmentStatus::APPROVED);
    }

    /**
     * @param  Builder<OpportunityCommissionAdjustment>  $query
     * @return Builder<OpportunityCommissionAdjustment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CommissionAdjustmentStatus::PENDING);
    }

    /**
     * @param  Builder<OpportunityCommissionAdjustment>  $query
     * @return Builder<OpportunityCommissionAdjustment>
     */
    public function scopeIncrease(Builder $query): Builder
    {
        return $query->where('direction', CommissionAdjustmentDirection::INCREASE);
    }

    /**
     * @param  Builder<OpportunityCommissionAdjustment>  $query
     * @return Builder<OpportunityCommissionAdjustment>
     */
    public function scopeDecrease(Builder $query): Builder
    {
        return $query->where('direction', CommissionAdjustmentDirection::DECREASE);
    }
}
