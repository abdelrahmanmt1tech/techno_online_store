<?php

namespace App\Models\Tenant;

use App\Enums\Pos\CashierSessionStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashierSession extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'pos_register_id',
        'branch_id',
        'user_id',
        'status',
        'device_name',
        'opening_balance',
        'expected_balance',
        'actual_balance',
        'difference',
        'opening_notes',
        'closing_notes',
        'difference_reason',
        'opened_at',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'status' => CashierSessionStatus::class,
        'opening_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'closed_by');
    }

    public function cashMovements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function isOpen(): bool
    {
        return $this->status === CashierSessionStatus::Open;
    }
}
