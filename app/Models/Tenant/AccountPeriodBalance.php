<?php

namespace App\Models\Tenant;

use App\Enums\BalanceSide;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountPeriodBalance extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'financial_period_id',
        'account_tree_id',
        'opening_debit',
        'opening_credit',
        'movement_debit',
        'movement_credit',
        'closing_debit',
        'closing_credit',
        'net_balance',
        'balance_side',
    ];

    protected function casts(): array
    {
        return [
            'opening_debit' => 'decimal:2',
            'opening_credit' => 'decimal:2',
            'movement_debit' => 'decimal:2',
            'movement_credit' => 'decimal:2',
            'closing_debit' => 'decimal:2',
            'closing_credit' => 'decimal:2',
            'net_balance' => 'decimal:2',
            'balance_side' => BalanceSide::class,
        ];
    }

    public function financialPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'financial_period_id');
    }

    public function accountTree(): BelongsTo
    {
        return $this->belongsTo(AccountTree::class, 'account_tree_id');
    }
}
