<?php

namespace App\Models\Tenant;

use App\Services\Accounting\ResolveFinancialPeriodService;
use App\Services\Accounting\SafesBankBalanceService;
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
            SafesBankBalanceService::syncForEntry($entry);
        });

        static::deleted(function (Entry $entry): void {
            app(SyncOperationMetadataService::class)->refreshOperation($entry->operation_id, $entry->day_date);
            self::syncOperationTaxRegisterRows($entry);
            if ($entry->account_tree_id) {
                SafesBankBalanceService::recalculateForAccountTree((int) $entry->account_tree_id);
            }
        });

        static::restored(function (Entry $entry): void {
            app(SyncOperationMetadataService::class)->refreshOperation($entry->operation_id, $entry->day_date);
            self::syncOperationTaxRegisterRows($entry);
            SafesBankBalanceService::syncForEntry($entry);
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
        if (empty($entry->operation_id)) {
            return;
        }

        /** @var Operation|null $operation */
        $operation = Operation::query()->find($entry->operation_id);
        if (! $operation) {
            return;
        }

        // IMPORTANT:
        // Tax-register rows from Entry should be created ONLY for direct free/manual operations.
        // Any operation linked to a source document (Ticket/Reservation/etc.) keeps its own tax flow
        // to avoid duplicate AccountTax rows for the same operation.
        if (! empty($operation->service_type) || ! empty($operation->service_id)) {
            return;
        }

        // Extra guard: keep system-generated flows fully excluded.
        if ((bool) ($operation->is_system_generated ?? false)) {
            return;
        }

        $settings = Setting::query()
            ->whereIn('key', [
                'account_tax_register_purchase_tax_account_tree_id',
                'account_tax_register_sales_tax_account_tree_id',
            ])
            ->pluck('value', 'key');

        $purchaseAccountId = (int) ($settings['account_tax_register_purchase_tax_account_tree_id'] ?? 0);
        $salesAccountId = (int) ($settings['account_tax_register_sales_tax_account_tree_id'] ?? 0);

        if ($purchaseAccountId <= 0 && $salesAccountId <= 0) {
            return;
        }

        $aggregateTaxValue = function (int $accountTreeId) use ($operation): float {
            if ($accountTreeId <= 0) {
                return 0.0;
            }

            return round((float) $operation->entries()
                ->where('account_tree_id', $accountTreeId)
                ->get(['debit', 'credit'])
                ->sum(function (Entry $line): float {
                    return max((float) ($line->debit ?? 0), (float) ($line->credit ?? 0));
                }), 2);
        };

        $purchaseTaxValue = $aggregateTaxValue($purchaseAccountId);
        $salesTaxValue = $aggregateTaxValue($salesAccountId);

        self::upsertOperationTaxRegisterRow($operation, 'purchase_tax', $purchaseTaxValue);
        self::upsertOperationTaxRegisterRow($operation, 'sales_tax', $salesTaxValue);
    }

    protected static function upsertOperationTaxRegisterRow(Operation $operation, string $type, float $taxValue): void
    {
        $baseQuery = AccountTax::query()
            ->where('operation_id', $operation->id)
            ->whereNull('ticket_id')
            ->whereNull('reservation_id')
            ->whereNull('invoice_id')
            ->where('type', $type);

        if ($taxValue <= 0) {
            $baseQuery->delete();
            return;
        }

        $baseQuery->updateOrCreate(
            [
                'operation_id' => $operation->id,
                'ticket_id' => null,
                'reservation_id' => null,
                'invoice_id' => null,
                'type' => $type,
            ],
            [
                'tax_value' => $taxValue,
                'tax_types_id' => 1,
                'tax_percentage' => null,
                'is_returned' => false,
            ]
        );
    }
}
