<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountsCenterMovement extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'accounts_center_id',
        'ticket_id',
        'reservation_id',
        'operation_id',
        'linkable_type',
        'linkable_id',
        'amount',
        'debit',
        'credit',
        'movement_date',
        'movement_type',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'movement_date' => 'date',
    ];

    protected static function booted(): void
    {
        $recalculate = function (self $movement): void {
            if ($movement->accounts_center_id) {
                AccountsCenter::recalculateDebitCreditFromMovements((int) $movement->accounts_center_id);
            }
        };

        static::saved($recalculate);

        static::deleted(function (self $movement): void {
            if ($movement->accounts_center_id) {
                AccountsCenter::recalculateDebitCreditFromMovements((int) $movement->accounts_center_id);
            }
        });
    }

    public function accountsCenter(): BelongsTo
    {
        return $this->belongsTo(AccountsCenter::class, 'accounts_center_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}

