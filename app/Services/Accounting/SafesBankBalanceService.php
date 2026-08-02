<?php

namespace App\Services\Accounting;

use App\Models\Tenant\Entry;
use App\Models\SafesBank;
use Illuminate\Support\Facades\DB;

/**
 * Keeps {@see SafesBank::$balance} aligned with journal lines on the linked {@see AccountTree}.
 * Asset rule: balance = SUM(debit) − SUM(credit) for entries on safes_bank.account_tree_id.
 */
class SafesBankBalanceService
{
    /**
     * Net balance effect of one entry line on an asset (safe/bank) account.
     */
    public static function entryNetDelta(?float $debit, ?float $credit): float
    {
        return round((float) ($debit ?? 0) - (float) ($credit ?? 0), 2);
    }

    /**
     * After an entry is saved/updated/restored, recalc any safe whose tree id changed.
     */
    public static function syncForEntry(Entry $entry): void
    {
        $treeIds = array_filter([
            $entry->account_tree_id ? (int) $entry->account_tree_id : null,
        ]);

        if ($entry->wasChanged('account_tree_id')) {
            $original = $entry->getOriginal('account_tree_id');
            if ($original) {
                $treeIds[] = (int) $original;
            }
        }

        foreach (array_unique($treeIds) as $treeId) {
            self::recalculateForAccountTree((int) $treeId);
        }
    }

    /**
     * Recalculate balance for the safe linked to this account tree (no-op if none).
     */
    public static function recalculateForAccountTree(int $accountTreeId): void
    {
        if ($accountTreeId <= 0) {
            return;
        }

        $safeIds = SafesBank::query()
            ->where('account_tree_id', $accountTreeId)
            ->pluck('id');

        foreach ($safeIds as $safeId) {
            self::recalculateBalance((int) $safeId);
        }
    }

    /**
     * Rebuild stored balance from all non-deleted entries on the safe's account_tree_id.
     */
    public static function recalculateBalance(int $safesBankId): void
    {
        DB::transaction(function () use ($safesBankId): void {
            $safe = SafesBank::query()->lockForUpdate()->find($safesBankId);
            if (! $safe || ! $safe->account_tree_id) {
                return;
            }

            $row = Entry::query()
                ->where('account_tree_id', (int) $safe->account_tree_id)
                ->selectRaw('COALESCE(SUM(debit), 0) as sum_debit, COALESCE(SUM(credit), 0) as sum_credit')
                ->first();

            $balance = round((float) ($row->sum_debit ?? 0) - (float) ($row->sum_credit ?? 0), 2);

            $safe->forceFill(['balance' => $balance])->saveQuietly();
        });
    }

    /**
     * @return array{count: int, recalculated: int}
     */
    public static function recalculateAll(): array
    {
        $ids = SafesBank::query()
            ->whereNotNull('account_tree_id')
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            self::recalculateBalance((int) $id);
        }

        return [
            'count' => count($ids),
            'recalculated' => count($ids),
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, account_tree_id: int|null, stored_balance: float, computed_balance: float, delta: float, entries_count: int}>
     */
    public static function previewAll(): array
    {
        $rows = [];

        SafesBank::query()
            ->orderBy('id')
            ->get(['id', 'name', 'account_tree_id', 'balance'])
            ->each(function (SafesBank $safe) use (&$rows): void {
                $computed = 0.0;
                $entriesCount = 0;

                if ($safe->account_tree_id) {
                    $agg = Entry::query()
                        ->where('account_tree_id', (int) $safe->account_tree_id)
                        ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(debit), 0) as sum_debit, COALESCE(SUM(credit), 0) as sum_credit')
                        ->first();

                    $entriesCount = (int) ($agg->cnt ?? 0);
                    $computed = round((float) ($agg->sum_debit ?? 0) - (float) ($agg->sum_credit ?? 0), 2);
                }

                $stored = round((float) ($safe->balance ?? 0), 2);

                $rows[] = [
                    'id' => (int) $safe->id,
                    'name' => (string) $safe->name,
                    'account_tree_id' => $safe->account_tree_id ? (int) $safe->account_tree_id : null,
                    'stored_balance' => $stored,
                    'computed_balance' => $computed,
                    'delta' => round($computed - $stored, 2),
                    'entries_count' => $entriesCount,
                ];
            });

        return $rows;
    }
}
