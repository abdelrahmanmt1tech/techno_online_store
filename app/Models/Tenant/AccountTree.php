<?php

namespace App\Models\Tenant;

use DateTimeInterface;
use App\Enums\BalanceSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Openplain\FilamentTreeView\Concerns\HasTreeStructure;

class AccountTree extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;
    use HasTreeStructure;

    /**
     * Override package cascade delete behavior.
     *
     * Reason: `descendants()->delete()` relies on a recursive CTE + UPDATE,
     * which fails on some MariaDB versions/configurations in production.
     * We keep the tree structure features, but cascade-delete descendants
     * using iterative queries (no CTE) for maximum compatibility.
     */
    protected static function bootHasTreeStructure(): void
    {
        static::deleting(function (self $model): void {
            $descendantIds = $model->collectDescendantIdsIteratively();

            if (empty($descendantIds)) {
                return;
            }

            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                static::withTrashed()
                    ->whereIn($model->getKeyName(), $descendantIds)
                    ->forceDelete();
                return;
            }

            static::whereIn($model->getKeyName(), $descendantIds)->delete();
        });
    }

    /**
     * Collect all descendant ids without recursive CTEs.
     *
     * @return array<int, int>
     */
    protected function collectDescendantIdsIteratively(): array
    {
        $keyName = $this->getKeyName();
        $parentKey = $this->getParentKeyName();

        $visited = [];
        $result = [];

        $frontier = [(int) $this->getAttribute($keyName)];
        $visited[$frontier[0]] = true;

        while (! empty($frontier)) {
            $children = static::query()
                ->whereIn($parentKey, $frontier)
                ->pluck($keyName)
                ->map(fn ($id) => (int) $id)
                ->all();

            $frontier = [];
            foreach ($children as $id) {
                if (isset($visited[$id])) {
                    continue;
                }
                $visited[$id] = true;
                $result[] = $id;
                $frontier[] = $id;
            }
        }

        return $result;
    }


    protected $fillable = [
        'parent_id',
        'account_name',
        'account_code',
        'account_type',
        'level',
        'branch_id',
        'income_general_statement',
        'order',
        'main_acc_status',
        'is_disabled',

    ];


    public function subAccounts(): HasMany
    {
        return $this->hasMany(AccountTree::class, 'parent_id');
    }




    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'account_tree_id');
    }

    public function periodBalances(): HasMany
    {
        return $this->hasMany(AccountPeriodBalance::class, 'account_tree_id');
    }


    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccountTree::class ,'parent_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    protected function casts(): array
    {
        return [
//            'main_acc_status' => 'boolean',
            'is_disabled' => 'boolean',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_disabled', false);
    }

    /**
     * Collect this account id + descendants up to 5 levels.
     *
     * @return array<int, int>
     */
    public function collectAccountIdsForTotals(?int $maxDepth = null): array
    {
        $accounts = [];
        $visited = [];

        $walk = function (self $node, int $depth) use (&$walk, &$accounts, &$visited, $maxDepth): void {
            if (isset($visited[$node->id])) {
                return;
            }

            $visited[$node->id] = true;
            $accounts[] = (int) $node->id;

            if ($maxDepth !== null && $depth >= $maxDepth) {
                return;
            }

            $children = $node->relationLoaded('subAccounts')
                ? $node->subAccounts
                : $node->subAccounts()->get();

            foreach ($children as $child) {
                $walk($child, $depth + 1);
            }
        };

        $walk($this, 0);

        return $accounts;
    }

    /**
     * Debit & credit totals for this account (including descendants) for a branch.
     *
     * @return array{debit: float, credit: float, net_abs: float}
     */
    public function accountTotalsDebitCreditForBranch(int $branch_id, ?int $year = null): array
    {
        $year ??= (int) date('Y');
        $accounts_list = $this->collectAccountIdsForTotals();

        $row = DB::table('entries')
            ->selectRaw('COALESCE(SUM(debit), 0) AS debit_sum, COALESCE(SUM(credit), 0) AS credit_sum')
            ->where('linkable_type', Branch::class)
            ->where('linkable_id', $branch_id)
            ->whereIn('account_tree_id', $accounts_list)
            ->whereNull('deleted_at')
            ->whereNotNull('day_date')
            ->whereYear('day_date', $year)
            ->first();

        $debit = (float) ($row->debit_sum ?? 0);
        $credit = (float) ($row->credit_sum ?? 0);

        return [
            'debit' => $debit,
            'credit' => $credit,
            'net_abs' => abs($debit - $credit),
        ];
    }

    /**
     * Debit & credit totals for this account (including descendants) for a date range (all branches).
     *
     * @return array{debit: float, credit: float, net_abs: float}
     */
    public function accountTotalsDebitCreditForDateRange(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $accounts_list = $this->collectAccountIdsForTotals();

        $row = DB::table('entries')
            ->selectRaw('COALESCE(SUM(debit), 0) AS debit_sum, COALESCE(SUM(credit), 0) AS credit_sum')
            ->whereIn('account_tree_id', $accounts_list)
            ->whereNull('deleted_at')
            ->whereNotNull('day_date')
            ->whereBetween('day_date', [
                $from->format('Y-m-d'),
                $to->format('Y-m-d'),
            ])
            ->first();

        $debit = (float) ($row->debit_sum ?? 0);
        $credit = (float) ($row->credit_sum ?? 0);

        return [
            'debit' => $debit,
            'credit' => $credit,
            'net_abs' => abs($debit - $credit),
        ];
    }

    /**
     * Debit & credit totals for this account (including descendants) for a branch within a date range.
     *
     * @return array{debit: float, credit: float, net_abs: float}
     */
    public function accountTotalsDebitCreditForBranchAndDateRange(int $branchId, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $accountsList = $this->collectAccountIdsForTotals();

        $row = DB::table('entries')
            ->selectRaw('COALESCE(SUM(debit), 0) AS debit_sum, COALESCE(SUM(credit), 0) AS credit_sum')
            ->where('linkable_type', Branch::class)
            ->where('linkable_id', $branchId)
            ->whereIn('account_tree_id', $accountsList)
            ->whereNull('deleted_at')
            ->whereNotNull('day_date')
            ->whereBetween('day_date', [
                $from->format('Y-m-d'),
                $to->format('Y-m-d'),
            ])
            ->first();

        $debit = (float) ($row->debit_sum ?? 0);
        $credit = (float) ($row->credit_sum ?? 0);

        return [
            'debit' => $debit,
            'credit' => $credit,
            'net_abs' => abs($debit - $credit),
        ];
    }

    public function accountTotalsNetForBranch($branch_id, $year = null)
    {
        $totals = $this->accountTotalsDebitCreditForBranch((int) $branch_id, $year ? (int) $year : null);

        // Keep legacy behavior: absolute difference.
        return $totals['net_abs'];
    }

    public function isPostable(): bool
    {
//        return true ;  //todo
        return $this->main_acc_status === 'sub';
    }

    public function normalBalanceSide(): BalanceSide
    {
        return $this->account_type === 'credit'
            ? BalanceSide::CREDIT
            : BalanceSide::DEBIT;
    }

    public function isIncomeStatementAccount(): bool
    {
        return $this->income_general_statement === 'income';
    }

    public function isBalanceSheetAccount(): bool
    {
//        return 1 ;
        return $this->income_general_statement === 'general';
    }

    public function isRevenueAccount(): bool
    {
        return 1 ;
        return $this->isIncomeStatementAccount() && $this->account_type === 'credit';
    }

    public function isExpenseAccount(): bool
    {
        return 1 ;
        return $this->isIncomeStatementAccount() && $this->account_type === 'debit';
    }


}
