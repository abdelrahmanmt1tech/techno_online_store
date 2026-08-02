<?php

namespace App\Services\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Enums\OperationType;
use App\Enums\PeriodClosingStatus;
use App\Enums\PeriodTransferStatus;
use App\Models\Tenant\AccountPeriodBalance;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\PeriodClosing;
use App\Models\Tenant\PeriodTransfer;
use App\Models\TenantUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CarryForwardPeriodService
{
    public function __construct(
        protected AccountingOperationWriter $operationWriter,
        protected BuildAccountPeriodBalancesService $buildBalances,
    ) {
    }

    public function handle(FinancialPeriod $fromPeriod, FinancialPeriod $toPeriod, ?TenantUser $user = null, ?string $notes = null): PeriodTransfer
    {
        $user ??= Auth::user();

        if (! $fromPeriod->isClosed()) {
            throw ValidationException::withMessages(['from_period_id' => __('dashboard.financial_periods.messages.source_period_must_be_closed')]);
        }

        if (! $toPeriod->isOpen()) {
            throw ValidationException::withMessages(['to_period_id' => __('dashboard.financial_periods.messages.target_period_must_be_open')]);
        }

        if ($fromPeriod->id === $toPeriod->id) {
            throw ValidationException::withMessages(['to_period_id' => __('dashboard.financial_periods.messages.transfer_requires_distinct_period')]);
        }

        $existing = PeriodTransfer::query()
            ->where('from_period_id', $fromPeriod->id)
            ->where('to_period_id', $toPeriod->id)
            ->where('status', PeriodTransferStatus::COMPLETED)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages(['to_period_id' => __('dashboard.financial_periods.messages.transfer_already_exists')]);
        }

        if (! $fromPeriod->balances()->exists()) {
            $this->buildBalances->handle($fromPeriod);
        }

        $balances = AccountPeriodBalance::query()
            ->with('accountTree')
            ->where('financial_period_id', $fromPeriod->id)
            ->get()
            ->filter(fn (AccountPeriodBalance $balance): bool => $balance->accountTree?->isBalanceSheetAccount() ?? false);
            //dd($balances);
        $entries = [];
        foreach ($balances as $balance) {
            if ((float) $balance->net_balance <= 0) {
                continue;
            }

            $account = $balance->accountTree;
            if (! $account?->isPostable()) {
                continue;
            }

            $entries[] = [
                'account_tree_id' => $account->id,
                'debit' => $balance->balance_side?->value === 'debit' ? (float) $balance->net_balance : null,
                'credit' => $balance->balance_side?->value === 'credit' ? (float) $balance->net_balance : null,
                'notes' => __('dashboard.financial_periods.messages.carry_forward_line', ['account' => $account->account_name]),
                'day_date' => $toPeriod->start_date->toDateString(),
            ];
        }

        if ($entries === []) {
            throw ValidationException::withMessages(['from_period_id' => __('dashboard.financial_periods.messages.no_balances_to_carry_forward')]);
        }

        return DB::transaction(function () use ($fromPeriod, $toPeriod, $user, $notes, $entries): PeriodTransfer {
            $openingOperation = $this->operationWriter->createOperationWithEntries([
                'financial_period_id' => $toPeriod->id,
                'date' => $toPeriod->start_date->toDateString(),
                'comment' => __('dashboard.financial_periods.messages.carry_forward_comment', [
                    'from' => $fromPeriod->name,
                    'to' => $toPeriod->name,
                ]),
                'settlement' => true,
                'status' => true,
                'operation_type' => OperationType::CARRY_FORWARD,
                'is_posted' => true,
                'posted_at' => Carbon::now(),
                'posted_by' => $user?->id,
                'is_system_generated' => true,
            ], $entries);

            $transfer = PeriodTransfer::query()->updateOrCreate(
                [
                    'from_period_id' => $fromPeriod->id,
                    'to_period_id' => $toPeriod->id,
                ],
                [
                    'opening_operation_id' => $openingOperation->id,
                    'status' => PeriodTransferStatus::COMPLETED,
                    'transferred_at' => Carbon::now(),
                    'transferred_by' => $user?->id,
                    'reversed_at' => null,
                    'reversed_by' => null,
                    'notes' => $notes,
                ]
            );

            PeriodClosing::query()
                ->where('financial_period_id', $fromPeriod->id)
                ->update([
                    'carry_forward_operation_id' => $openingOperation->id,
                    'status' => PeriodClosingStatus::COMPLETED,
                ]);

            return $transfer;
        });
    }
}
