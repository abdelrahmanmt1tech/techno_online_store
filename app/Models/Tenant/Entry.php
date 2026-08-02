<?php

namespace App\Models\Tenant;

use App\Services\Accounting\ResolveFinancialPeriodService;
use App\Services\Accounting\SyncOperationMetadataService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Entry extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected static bool $skipFinancialPeriodGuard = false;

    protected $fillable = [
        'operation_id',
        'account_tree_id',
        'debit',
        'credit',
        'notes',
        'day_date',
        'linkable_type',
        'linkable_id',
        'entry_type',
        'is_locked',
        'branch_id',
    ];

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function booted()
    {
        // Ensure entries always inherit the same "linkable" (e.g. Branch) from their parent operation
        // on create AND on update (e.g. if operation_id changes).
        static::saving(function (Entry $entry): void {
            if (! self::$skipFinancialPeriodGuard) {
                if ($entry->exists && $entry->is_locked) {
                    throw ValidationException::withMessages([
                        'entry' => __('dashboard.financial_periods.messages.entry_locked'),
                    ]);
                }

                app(ResolveFinancialPeriodService::class)->ensureEntryIsWritable($entry);
            }

            if (empty($entry->operation_id)) {
                return;
            }

            $operation = $entry->relationLoaded('operation')
                ? $entry->operation
                : $entry->operation()->first(['id', 'date', 'linkable_type', 'linkable_id']);

            if (!$operation) {
                return;
            }

            // Keep Entry in sync with its Operation.
            $entry->linkable_type = $operation->linkable_type;
            $entry->linkable_id = $operation->linkable_id;
            $entry->day_date ??= $operation->date?->toDateString();

            if (empty($entry->branch_id) && $operation->linkable_type === Branch::class) {
                $entry->branch_id = $operation->linkable_id;
            }
        });

        static::deleting(function (Entry $entry): void {
            if (self::$skipFinancialPeriodGuard) {
                return;
            }

            if ($entry->is_locked) {
                throw ValidationException::withMessages([
                    'entry' => __('dashboard.financial_periods.messages.entry_locked'),
                ]);
            }

            app(ResolveFinancialPeriodService::class)->ensureEntryIsWritable($entry);
        });

        static::saved(function (Entry $entry): void {
            app(SyncOperationMetadataService::class)->refreshOperation($entry->operation_id, $entry->day_date);
            self::syncOperationTaxRegisterRows($entry);
            // SafesBank not ported — cash balances stay in POS drawers.
        });

        static::deleted(function (Entry $entry): void {
            app(SyncOperationMetadataService::class)->refreshOperation($entry->operation_id, $entry->day_date);
            self::syncOperationTaxRegisterRows($entry);
        });

        static::restored(function (Entry $entry): void {
            app(SyncOperationMetadataService::class)->refreshOperation($entry->operation_id, $entry->day_date);
            self::syncOperationTaxRegisterRows($entry);
        });
    }

    public function accountTree(): BelongsTo
    {
        return $this->belongsTo(AccountTree::class);
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected function casts(): array
    {
        return [
            'day_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'is_locked' => 'boolean',
        ];
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

    protected static function syncOperationTaxRegisterRows(Entry $entry): void
    {
        // AccountTax / TaxType / ZATCA tax register not ported to techno.
    }

    protected static function upsertOperationTaxRegisterRow(Operation $operation, string $type, float $taxValue): void
    {
        // no-op
    }
}
