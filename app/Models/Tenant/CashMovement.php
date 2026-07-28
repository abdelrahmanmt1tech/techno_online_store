<?php

namespace App\Models\Tenant;

use App\Enums\Pos\CashMovementType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'cashier_session_id',
        'cash_drawer_id',
        'type',
        'amount',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'type' => CashMovementType::class,
        'amount' => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }

    public function cashDrawer(): BelongsTo
    {
        return $this->belongsTo(CashDrawer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }
}
