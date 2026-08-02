<?php

namespace App\Models\Tenant;

use App\Enums\OperationType;
use App\Services\Accounting\ResolveFinancialPeriodService;
use App\Services\Accounting\SyncOperationMetadataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Operation extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected static bool $skipFinancialPeriodGuard = false;

    protected $fillable = [
        'financial_period_id',
        'date',
        'comment',
        'reference_no',
        'settlement',
        'status',
        'operation_type',
        'total_debit',
        'total_credit',
        'is_posted',
        'posted_at',
        'posted_by',
        'is_locked',
        'locked_at',
        'locked_by',
        'is_system_generated',
        'linkable_type',
        'linkable_id',
        'service_type',
        'service_id',
        'source_operation_id',
    ];

    protected $appends = [
        'total_debit',
        'total_credit',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $operation): void {
            app(SyncOperationMetadataService::class)->applyBeforeSave($operation);

            if (self::$skipFinancialPeriodGuard) {
                return;
            }

            if ($operation->exists && $operation->is_locked) {
                throw ValidationException::withMessages([
                    'operation' => __('dashboard.financial_periods.messages.operation_locked'),
                ]);
            }

            app(ResolveFinancialPeriodService::class)->ensureOperationDateIsWritable(
                $operation->date,
                $operation->financial_period_id
            );
        });

        static::deleting(function (self $operation): void {
            if (! self::$skipFinancialPeriodGuard) {
                if ($operation->is_locked) {
                    throw ValidationException::withMessages([
                        'operation' => __('dashboard.financial_periods.messages.operation_locked'),
                    ]);
                }

                app(ResolveFinancialPeriodService::class)->ensureOperationDateIsWritable(
                    $operation->date,
                    $operation->financial_period_id
                );
            }

            // AccountStatement intentionally not synced in techno port.
        });

        static::restored(function (self $operation): void {
            // AccountStatement intentionally not synced in techno port.
        });

        static::saved(function (self $operation): void {
            if (! $operation->wasChanged('date')) {
                return;
            }

            $operationDate = $operation->date?->toDateString();
            if (! $operationDate) {
                return;
            }

            Entry::query()
                ->where('operation_id', $operation->id)
                ->update([
                    'day_date' => $operationDate,
                ]);
        });
    }

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'settlement' => 'boolean',
            'status' => 'boolean',
            'operation_type' => OperationType::class,
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'is_posted' => 'boolean',
            'posted_at' => 'datetime',
            'is_locked' => 'boolean',
            'locked_at' => 'datetime',
            'is_system_generated' => 'boolean',
        ];
    }

    public function getTotalDebitAttribute()
    {
        return (float) ($this->attributes['total_debit'] ?? $this->entries()->sum('debit'));
    }

    public function getTotalCreditAttribute()
    {
        return (float) ($this->attributes['total_credit'] ?? $this->entries()->sum('credit'));
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'operation_id');
    }

    public function creditEntries(): HasMany
    {
        return $this->hasMany(Entry::class, 'operation_id')->whereNull("debit");
    }

    public function debitEntries(): HasMany
    {
        return $this->hasMany(Entry::class, 'operation_id')->whereNull("credit");
    }

    public function financialPeriod(): BelongsTo
    {
        return $this->belongsTo(FinancialPeriod::class, 'financial_period_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'posted_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'locked_by');
    }

    public function sourceOperation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_operation_id');
    }

    public function childOperations(): HasMany
    {
        return $this->hasMany(self::class, 'source_operation_id');
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The source document that generated this operation (Ticket, Reservation, etc.).
     */
    public function service(): MorphTo
    {
        return $this->morphTo();
    }

    public function invoice()
    {
        // Operation can be invoiceable when creating manual entries with tax/invoice flow.
        return $this->morphOne(Invoice::class, 'invoiceable');
    }

    public function accountTaxes(): HasMany
    {
        return $this->hasMany(AccountTax::class, 'operation_id');
    }

    public function accountsCenterMovements(): HasMany
    {
        return $this->hasMany(AccountsCenterMovement::class, 'operation_id');
    }

    public function isOpening(): bool
    {
        return $this->operation_type === OperationType::OPENING;
    }

    public function isClosing(): bool
    {
        return in_array($this->operation_type, [
            OperationType::CLOSING_REVENUE,
            OperationType::CLOSING_EXPENSE,
            OperationType::CLOSING_PROFIT_LOSS,
        ], true);
    }

    public function isCarryForward(): bool
    {
        return $this->operation_type === OperationType::CARRY_FORWARD;
    }

    public function isSystemGenerated(): bool
    {
        return (bool) $this->is_system_generated;
    }

    public function refreshTotalsFromEntries(): void
    {
        $this->forceFill([
            'total_debit' => (float) $this->entries()->sum('debit'),
            'total_credit' => (float) $this->entries()->sum('credit'),
        ])->saveQuietly();
    }

    public static function withoutFinancialPeriodGuard(callable $callback): mixed
    {
        $previous = self::$skipFinancialPeriodGuard;
        self::$skipFinancialPeriodGuard = true;

        try {
            return $callback();
        } finally {
            self::$skipFinancialPeriodGuard = $previous;
        }
    }
}
