<?php

namespace App\Services\Accounting;

use Illuminate\Validation\ValidationException;

use App\Enums\FinancialPeriodStatus;
use App\Enums\OperationType;
use App\Enums\PeriodClosingStatus;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Operation;
use App\Models\Tenant\PeriodClosing;
use App\Models\Tenant\TenantSetting;
use App\Models\TenantUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CloseFinancialPeriodService
{
    public function __construct(
        protected AccountingOperationWriter $operationWriter,
        protected AccountBalanceResolver $balanceResolver,
        protected BuildAccountPeriodBalancesService $buildBalances,
    ) {}

    public function handle(FinancialPeriod $period, ?TenantUser $user = null, ?string $notes = null): PeriodClosing
    {
        $user ??= Auth::user();

        if (! $period->isOpen()) {
            throw ValidationException::withMessages(['financial_period_id' => __('dashboard.financial_periods.messages.period_must_be_open')]);
        }

        $existingCompleted = PeriodClosing::query()
            ->where('financial_period_id', $period->id)
            ->where('status', PeriodClosingStatus::COMPLETED)
            ->exists();

        if ($existingCompleted) {
            throw ValidationException::withMessages(['financial_period_id' => __('dashboard.financial_periods.messages.period_already_closed')]);
        }

        $incomeSummaryAccountId = (int) (TenantSetting::getValue('income_summary_account_tree_id') ?? 0);

        $retainedEarningsAccountId = (int) (TenantSetting::getValue('retained_earnings_account_tree_id') ?? 0);

        $incomeSummaryAccount = AccountTree::query()->find($incomeSummaryAccountId);
        $retainedEarningsAccount = AccountTree::query()->find($retainedEarningsAccountId);

        if (! $incomeSummaryAccount?->isPostable()) {
            throw ValidationException::withMessages(['income_summary_account_tree_id' => __('dashboard.financial_periods.messages.income_summary_account_required')]);
        }

        if (! $retainedEarningsAccount?->isPostable()) {
            throw ValidationException::withMessages(['retained_earnings_account_tree_id' => __('dashboard.financial_periods.messages.retained_earnings_account_required')]);
        }

        $closing = PeriodClosing::query()->firstOrNew([
            'financial_period_id' => $period->id,
        ]);
        $closing->fill([
            'status' => PeriodClosingStatus::PENDING,
            'notes' => $notes,
        ])->save();

        try {
            DB::transaction(function () use ($period, $user, $notes, $closing, $incomeSummaryAccount, $retainedEarningsAccount): void {
                $period->forceFill([
                    'status' => FinancialPeriodStatus::CLOSING,
                ])->save();

                $balances = $this->collectIncomeStatementBalances($period);

                $revenueEntries = [];
                $expenseEntries = [];
                $revenueTotal = 0.0;
                $expenseTotal = 0.0;

                foreach ($balances as $balance) {
                    $account = $balance['account'];
                    $resolved = $this->balanceResolver->resolveClosingBalance(
                        $balance['debit'],
                        $balance['credit'],
                        $account
                    );

                    if (($resolved['net_balance'] ?? 0.0) <= 0) {
                        continue;
                    }

                    $closingValue = (float) $resolved['net_balance'];
                    $entryDate = $period->end_date->toDateString();

                    if ($account->isRevenueAccount()) {
                        $revenueEntries[] = [
                            'account_tree_id' => $account->id,
                            'debit' => $closingValue,
                            'credit' => null,
                            'notes' => __('dashboard.financial_periods.messages.revenue_closing_line', ['account' => $account->account_name]),
                            'day_date' => $entryDate,
                        ];
                        $revenueEntries[] = [
                            'account_tree_id' => $incomeSummaryAccount->id,
                            'debit' => null,
                            'credit' => $closingValue,
                            'notes' => __('dashboard.financial_periods.messages.income_summary_line'),
                            'day_date' => $entryDate,
                        ];
                        $revenueTotal += $closingValue;
                    }

                    if ($account->isExpenseAccount()) {
                        $expenseEntries[] = [
                            'account_tree_id' => $account->id,
                            'debit' => null,
                            'credit' => $closingValue,
                            'notes' => __('dashboard.financial_periods.messages.expense_closing_line', ['account' => $account->account_name]),
                            'day_date' => $entryDate,
                        ];
                        $expenseEntries[] = [
                            'account_tree_id' => $incomeSummaryAccount->id,
                            'debit' => $closingValue,
                            'credit' => null,
                            'notes' => __('dashboard.financial_periods.messages.income_summary_line'),
                            'day_date' => $entryDate,
                        ];
                        $expenseTotal += $closingValue;
                    }
                }

                $revenueOperation = $revenueEntries !== []
                    ? $this->operationWriter->createOperationWithEntries([
                        'financial_period_id' => $period->id,
                        'date' => $period->end_date->toDateString(),
                        'comment' => __('dashboard.financial_periods.messages.revenue_closing_comment', ['period' => $period->name]),
                        'settlement' => true,
                        'status' => true,
                        'operation_type' => OperationType::CLOSING_REVENUE,
                        'is_posted' => true,
                        'posted_at' => Carbon::now(),
                        'posted_by' => $user?->id,
                        'is_system_generated' => true,
                    ], $revenueEntries)
                    : null;

                $expenseOperation = $expenseEntries !== []
                    ? $this->operationWriter->createOperationWithEntries([
                        'financial_period_id' => $period->id,
                        'date' => $period->end_date->toDateString(),
                        'comment' => __('dashboard.financial_periods.messages.expense_closing_comment', ['period' => $period->name]),
                        'settlement' => true,
                        'status' => true,
                        'operation_type' => OperationType::CLOSING_EXPENSE,
                        'is_posted' => true,
                        'posted_at' => Carbon::now(),
                        'posted_by' => $user?->id,
                        'is_system_generated' => true,
                    ], $expenseEntries)
                    : null;

                $profitLossEntries = [];
                $netProfit = round($revenueTotal - $expenseTotal, 2);

                if ($netProfit > 0) {
                    $profitLossEntries[] = [
                        'account_tree_id' => $incomeSummaryAccount->id,
                        'debit' => $netProfit,
                        'credit' => null,
                        'notes' => __('dashboard.financial_periods.messages.profit_loss_transfer_line'),
                        'day_date' => $period->end_date->toDateString(),
                    ];
                    $profitLossEntries[] = [
                        'account_tree_id' => $retainedEarningsAccount->id,
                        'debit' => null,
                        'credit' => $netProfit,
                        'notes' => __('dashboard.financial_periods.messages.retained_earnings_line'),
                        'day_date' => $period->end_date->toDateString(),
                    ];
                } elseif ($netProfit < 0) {
                    $loss = abs($netProfit);
                    $profitLossEntries[] = [
                        'account_tree_id' => $retainedEarningsAccount->id,
                        'debit' => $loss,
                        'credit' => null,
                        'notes' => __('dashboard.financial_periods.messages.retained_earnings_line'),
                        'day_date' => $period->end_date->toDateString(),
                    ];
                    $profitLossEntries[] = [
                        'account_tree_id' => $incomeSummaryAccount->id,
                        'debit' => null,
                        'credit' => $loss,
                        'notes' => __('dashboard.financial_periods.messages.profit_loss_transfer_line'),
                        'day_date' => $period->end_date->toDateString(),
                    ];
                }

                $profitLossOperation = $profitLossEntries !== []
                    ? $this->operationWriter->createOperationWithEntries([
                        'financial_period_id' => $period->id,
                        'date' => $period->end_date->toDateString(),
                        'comment' => __('dashboard.financial_periods.messages.profit_loss_comment', ['period' => $period->name]),
                        'settlement' => true,
                        'status' => true,
                        'operation_type' => OperationType::CLOSING_PROFIT_LOSS,
                        'is_posted' => true,
                        'posted_at' => Carbon::now(),
                        'posted_by' => $user?->id,
                        'is_system_generated' => true,
                    ], $profitLossEntries)
                    : null;

                $this->buildBalances->handle($period);

                Operation::query()
                    ->where('financial_period_id', $period->id)
                    ->update([
                        'is_locked' => true,
                        'locked_at' => Carbon::now(),
                        'locked_by' => $user?->id,
                    ]);

                $period->forceFill([
                    'status' => FinancialPeriodStatus::CLOSED,
                    'closed_at' => Carbon::now(),
                    'closed_by' => $user?->id,
                    'notes' => $notes ?: $period->notes,
                ])->save();

                $closing->forceFill([
                    'revenue_closing_operation_id' => $revenueOperation?->id,
                    'expense_closing_operation_id' => $expenseOperation?->id,
                    'profit_loss_operation_id' => $profitLossOperation?->id,
                    'status' => PeriodClosingStatus::COMPLETED,
                    'closed_at' => Carbon::now(),
                    'closed_by' => $user?->id,
                    'notes' => $notes,
                ])->save();
            });
        } catch (\Throwable $throwable) {
            $closing->forceFill([
                'status' => PeriodClosingStatus::FAILED,
                'notes' => $notes ?: $throwable->getMessage(),
            ])->save();

            $period->forceFill([
                'status' => FinancialPeriodStatus::OPEN,
            ])->save();

            throw $throwable;
        }

        return $closing->fresh();
    }

    /**
     * @return array<int, array{account: AccountTree, debit: float, credit: float}>
     */
    protected function collectIncomeStatementBalances(FinancialPeriod $period): array
    {
        $effectiveDateSql = 'COALESCE(entries.day_date, DATE(operations.date))';

        $rows = DB::table('entries')
            ->join('operations', 'operations.id', '=', 'entries.operation_id')
            ->join('account_trees', 'account_trees.id', '=', 'entries.account_tree_id')
            ->whereNull('entries.deleted_at')
            ->whereNull('operations.deleted_at')
            ->where('account_trees.income_general_statement', 'income')
            ->whereRaw("{$effectiveDateSql} BETWEEN ? AND ?", [
                $period->start_date->toDateString(),
                $period->end_date->toDateString(),
            ])
            ->groupBy('entries.account_tree_id')
            ->selectRaw('entries.account_tree_id, SUM(COALESCE(entries.debit, 0)) AS debit_sum, SUM(COALESCE(entries.credit, 0)) AS credit_sum')
            ->get();

        $accounts = AccountTree::query()
            ->whereIn('id', $rows->pluck('account_tree_id')->all())
            ->get()
            ->keyBy('id');

        return $rows
            ->map(fn ($row): array => [
                'account' => $accounts->get($row->account_tree_id),
                'debit' => round((float) $row->debit_sum, 2),
                'credit' => round((float) $row->credit_sum, 2),
            ])
            ->filter(fn (array $row): bool => $row['account'] instanceof AccountTree)
            ->values()
            ->all();
    }
}
