<?php

namespace App\Services\Accounting;

use App\Enums\OperationType;
use App\Models\Tenant\FinancialPeriod;
use App\Models\TenantUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateOpeningEntryService
{
    public function __construct(
        protected AccountingOperationWriter $operationWriter,
    ) {
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     */
    public function handle(
        FinancialPeriod $period,
        array $entries,
        ?string $comment = null,
        ?string $referenceNo = null,
        ?User $user = null,
        ?string $date = null,
        bool $isSystemGenerated = false,
    ) {
        $user ??= Auth::user();

        if (! $period->isOpen()) {
            dd([
                'financial_period_id' => __('dashboard.financial_periods.messages.period_must_be_open'),
            ]);
        }

        $operationDate = $date ?: $period->start_date->toDateString();

        if (! $period->containsDate($operationDate)) {
            dd([
                'date' => __('dashboard.financial_periods.messages.date_outside_period'),
            ]);
        }

        return $this->operationWriter->createOperationWithEntries([
            'financial_period_id' => $period->id,
            'date' => $operationDate,
            'comment' => $comment ?: __('dashboard.financial_periods.messages.opening_entry_comment', [
                'period' => $period->name,
            ]),
            'reference_no' => $referenceNo,
            'settlement' => true,
            'status' => true,
            'operation_type' => OperationType::OPENING,
            'is_posted' => true,
            'posted_at' => Carbon::now(),
            'posted_by' => $user?->id,
            'is_locked' => false,
            'locked_at' => null,
            'locked_by' => null,
            'is_system_generated' => $isSystemGenerated,
        ], $entries);
    }
}
