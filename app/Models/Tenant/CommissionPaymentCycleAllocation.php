<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPaymentCycleAllocation extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'commission_payment_cycle_id',
        'opportunity_commission_id',
        'user_id',
        'effective_amount_snapshot',
        'net_paid_snapshot',
        'remaining_snapshot',
        'planned_payment_amount',
    ];

    protected function casts(): array
    {
        return [
            'effective_amount_snapshot' => 'decimal:2',
            'net_paid_snapshot' => 'decimal:2',
            'remaining_snapshot' => 'decimal:2',
            'planned_payment_amount' => 'decimal:2',
        ];
    }

    public function commissionPaymentCycle(): BelongsTo
    {
        return $this->belongsTo(CommissionPaymentCycle::class);
    }

    public function opportunityCommission(): BelongsTo
    {
        return $this->belongsTo(OpportunityCommission::class);
    }

    public function user(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class);
    }
}
