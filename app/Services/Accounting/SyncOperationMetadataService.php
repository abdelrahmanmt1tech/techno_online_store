<?php

namespace App\Services\Accounting;

use App\Enums\OperationType;
use App\Models\Tenant\Operation;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class SyncOperationMetadataService
{
    public function __construct(
        protected ResolveFinancialPeriodService $periodResolver,
    ) {
    }

    public function resolveEffectiveDate(
        DateTimeInterface|string|null $operationDate = null,
        DateTimeInterface|string|null $entryDate = null,
    ): ?Carbon {
        $candidate = $entryDate ?: $operationDate;

        if ($candidate instanceof CarbonInterface) {
            return Carbon::parse($candidate);
        }

        if ($candidate instanceof DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($candidate));
        }

        if (blank($candidate)) {
            return null;
        }

        return Carbon::parse($candidate);
    }

    public function applyBeforeSave(Operation $operation): void
    {
        $effectiveDate = $this->resolveEffectiveDate($operation->date);

        if ($effectiveDate) {
            $operation->date = $effectiveDate;
            $resolvedId = $this->periodResolver->resolveForDate($effectiveDate)?->id;
            $operation->financial_period_id = $resolvedId ?? $operation->financial_period_id;
        }

        if (blank($operation->operation_type)) {
            $operation->operation_type = OperationType::NORMAL;
        }

        if ($operation->exists) {
            return;
        }

        $operation->total_debit ??= 0;
        $operation->total_credit ??= 0;
        $operation->is_posted ??= false;
        $operation->is_locked ??= false;
        $operation->is_system_generated ??= false;
    }

    public function refreshOperation(Operation|int|null $operation, DateTimeInterface|string|null $entryDate = null): ?Operation
    {
        if (blank($operation)) {
            return null;
        }

        $operation = $operation instanceof Operation
            ? $operation
            : Operation::query()->find($operation);

        if (! $operation) {
            return null;
        }

        $firstEntryDate = $entryDate
            ?: $operation->entries()
                ->whereNotNull('day_date')
                ->orderBy('day_date')
                ->value('day_date');

        $effectiveDate = $this->resolveEffectiveDate($operation->date, $firstEntryDate);
        $totalDebit = round((float) $operation->entries()->sum('debit'), 2);
        $totalCredit = round((float) $operation->entries()->sum('credit'), 2);
        $isBalanced = $totalDebit > 0 && $totalDebit === $totalCredit;

        $resolvedPeriodId = $effectiveDate
            ? $this->periodResolver->resolveForDate($effectiveDate)?->id
            : null;

        // Never wipe an explicit financial_period_id when the resolver returns null (e.g. date edge cases).
        $financialPeriodId = $resolvedPeriodId ?? $operation->financial_period_id;

        $operation->forceFill([
            'date' => $effectiveDate ?: $operation->date,
            'financial_period_id' => $financialPeriodId,
            'operation_type' => $operation->operation_type ?: OperationType::NORMAL,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_posted' => $isBalanced ? true : (bool) $operation->is_posted,
            'posted_at' => $isBalanced ? ($operation->posted_at ?: now()) : $operation->posted_at,
        ])->saveQuietly();

        return $operation->fresh();
    }
}
