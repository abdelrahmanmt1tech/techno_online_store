<?php

namespace App\Services\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Enums\OperationType;
use App\Enums\PeriodClosingStatus;
use App\Enums\PeriodTransferStatus;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Operation;
use App\Models\Tenant\PeriodClosing;
use App\Models\Tenant\PeriodTransfer;
use App\Models\TenantUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReopenFinancialPeriodService
{
    public function __construct(
        protected AccountingOperationWriter $operationWriter,
    ) {
    }

    public function handle(FinancialPeriod $period, ?TenantUser $user = null, ?string $notes = null): FinancialPeriod
    {
        $user ??= Auth::user();

        if (! $period->canBeReopened()) {
            throw ValidationException::withMessages(['financial_period_id' => __('dashboard.financial_periods.messages.period_cannot_be_reopened')]);
        }

        return DB::transaction(function () use ($period, $user, $notes): FinancialPeriod {
            $closing = PeriodClosing::query()
                ->where('financial_period_id', $period->id)
                ->latest('id')
                ->first();

            if ($closing) {
                foreach ([
                    $closing->revenueClosingOperation,
                    $closing->expenseClosingOperation,
                    $closing->profitLossOperation,
                ] as $operation) {
                    if ($operation) {
                        $this->reverseIfNeeded($operation, $period, $user, $notes);
                    }
                }

                $closing->forceFill([
                    'status' => PeriodClosingStatus::REOPENED,
                    'reopened_at' => Carbon::now(),
                    'reopened_by' => $user?->id,
                    'notes' => $notes ?: $closing->notes,
                ])->save();
            }

            $transfers = PeriodTransfer::query()
                ->where('from_period_id', $period->id)
                ->where('status', PeriodTransferStatus::COMPLETED)
                ->get();

            foreach ($transfers as $transfer) {
                if ($transfer->openingOperation) {
                    $this->reverseIfNeeded($transfer->openingOperation, $transfer->toPeriod, $user, $notes);
                }

                $transfer->forceFill([
                    'status' => PeriodTransferStatus::REVERSED,
                    'reversed_at' => Carbon::now(),
                    'reversed_by' => $user?->id,
                    'notes' => $notes ?: $transfer->notes,
                ])->save();
            }

            Operation::query()
                ->where('financial_period_id', $period->id)
                ->where('is_system_generated', false)
                ->update([
                    'is_locked' => false,
                    'locked_at' => null,
                    'locked_by' => null,
                ]);

            $period->forceFill([
                'status' => FinancialPeriodStatus::OPEN,
                'reopened_at' => Carbon::now(),
                'reopened_by' => $user?->id,
                'closed_at' => null,
                'closed_by' => null,
                'notes' => $notes ?: $period->notes,
            ])->save();

            return $period->fresh();
        });
    }

    protected function reverseIfNeeded(Operation $operation, FinancialPeriod $financialPeriod, ?TenantUser $user = null, ?string $notes = null): void
    {
        $alreadyReversed = $operation->childOperations()
            ->where('operation_type', OperationType::REVERSAL)
            ->exists();

        if ($alreadyReversed) {
            return;
        }

        $this->operationWriter->reverseOperation(
            $operation,
            $financialPeriod,
            $user,
            $notes ?: __('dashboard.financial_periods.messages.reopen_reversal_comment', [
                'period' => $financialPeriod->name,
            ])
        );
    }
}
