<?php

namespace App\Models\Tenant;

use App\Enums\Pos\CashMovementType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class CashMovement extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'cashier_session_id',
        'cash_drawer_id',
        'type',
        'payment_method_type',
        'payment_method_code',
        'amount',
        'direction',
        'sale_id',
        'sales_invoice_id',
        'invoice_payment_id',
        'reverses_movement_id',
        'is_reversal',
        'reference',
        'notes',
        'meta',
        'created_by',
    ];

    protected $casts = [
        'type' => CashMovementType::class,
        'amount' => 'decimal:2',
        'is_reversal' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new LogicException(__('commerce.validation.cash_movement_immutable'));
        });

        static::deleting(function (): void {
            throw new LogicException(__('commerce.validation.cash_movement_immutable'));
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashierSession::class, 'cashier_session_id');
    }

    public function cashDrawer(): BelongsTo
    {
        return $this->belongsTo(CashDrawer::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_movement_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }
}
