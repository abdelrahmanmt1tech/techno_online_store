<?php

namespace App\Models\Tenant;

use App\Enums\PeriodClosingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodClosing extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'financial_period_id',
        'revenue_closing_operation_id',
        'expense_closing_operation_id',
        'profit_loss_operation_id',
        'carry_forward_operation_id',
        'status',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PeriodClosingStatus::class,
            'closed_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function financialPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'financial_period_id');
    }

    public function revenueClosingOperation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'revenue_closing_operation_id');
    }

    public function expenseClosingOperation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'expense_closing_operation_id');
    }

    public function profitLossOperation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'profit_loss_operation_id');
    }

    public function carryForwardOperation(): BelongsTo
    {
        return $this->belongsTo(Operation::class, 'carry_forward_operation_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'closed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'reopened_by');
    }
}
