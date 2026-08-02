<?php

namespace App\Services\Accounting;

use App\Models\Tenant\AccountPeriodBalance;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\FinancialPeriod;
use Illuminate\Support\Facades\DB;

class BuildAccountPeriodBalancesService
{
    public function __construct(
        protected AccountBalanceResolver $balanceResolver,
    ) {
    }

    public function handle(FinancialPeriod $period): void
    {
        $startDate = $period->start_date->toDateString();
        $endDate = $period->end_date->toDateString();
        $effectiveDateSql = "COALESCE(entries.day_date, DATE(operations.date))";

        $rows = DB::table('entries')
            ->join('operations', 'operations.id', '=', 'entries.operation_id')
            ->join('account_trees', 'account_trees.id', '=', 'entries.account_tree_id')
            ->whereNull('entries.deleted_at')
            ->whereNull('operations.deleted_at')
            ->whereRaw("{$effectiveDateSql} <= ?", [$endDate])
            ->selectRaw('
                entries.account_tree_id,
                account_trees.account_type,
                account_trees.income_general_statement,
                account_trees.main_acc_status,
                SUM(CASE WHEN ' . $effectiveDateSql . ' < ? THEN COALESCE(entries.debit, 0) ELSE 0 END) AS opening_debit,
                SUM(CASE WHEN ' . $effectiveDateSql . ' < ? THEN COALESCE(entries.credit, 0) ELSE 0 END) AS opening_credit,
                SUM(CASE WHEN ' . $effectiveDateSql . ' BETWEEN ? AND ? THEN COALESCE(entries.debit, 0) ELSE 0 END) AS movement_debit,
                SUM(CASE WHEN ' . $effectiveDateSql . ' BETWEEN ? AND ? THEN COALESCE(entries.credit, 0) ELSE 0 END) AS movement_credit
            ', [$startDate, $startDate, $startDate, $endDate, $startDate, $endDate])
            ->groupBy('entries.account_tree_id', 'account_trees.account_type', 'account_trees.income_general_statement', 'account_trees.main_acc_status')
            ->get();

        DB::transaction(function () use ($period, $rows): void {
            AccountPeriodBalance::query()
                ->where('financial_period_id', $period->id)
                ->delete();

            $accounts = AccountTree::query()
                ->whereIn('id', $rows->pluck('account_tree_id')->all())
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                $closingDebit = round((float) $row->opening_debit + (float) $row->movement_debit, 2);
                $closingCredit = round((float) $row->opening_credit + (float) $row->movement_credit, 2);
                $account = $accounts->get($row->account_tree_id);
                $closingBalance = $this->balanceResolver->resolveClosingBalance($closingDebit, $closingCredit, $account);

                if (
                    (float) $row->opening_debit === 0.0 &&
                    (float) $row->opening_credit === 0.0 &&
                    (float) $row->movement_debit === 0.0 &&
                    (float) $row->movement_credit === 0.0
                ) {
                    continue;
                }

                AccountPeriodBalance::query()->create([
                    'financial_period_id' => $period->id,
                    'account_tree_id' => $row->account_tree_id,
                    'opening_debit' => round((float) $row->opening_debit, 2),
                    'opening_credit' => round((float) $row->opening_credit, 2),
                    'movement_debit' => round((float) $row->movement_debit, 2),
                    'movement_credit' => round((float) $row->movement_credit, 2),
                    'closing_debit' => $closingDebit,
                    'closing_credit' => $closingCredit,
                    'net_balance' => $closingBalance['net_balance'],
                    'balance_side' => $closingBalance['balance_side']?->value,
                ]);
            }
        });
    }
}
