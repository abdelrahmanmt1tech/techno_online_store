<?php

namespace App\Services\Accounting;

use App\Enums\FinancialPeriodStatus;
use App\Models\Tenant\Entry;
use App\Models\Tenant\FinancialPeriod;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ResolveFinancialPeriodService
{
    public function resolveForDate(DateTimeInterface|string|null $date): ?FinancialPeriod
    {
        $normalized = $this->normalizeDate($date);

        if (! $normalized) {
            return null;
        }

        return FinancialPeriod::query()
            ->whereDate('start_date', '<=', $normalized)
            ->whereDate('end_date', '>=', $normalized)
            ->orderByDesc('start_date')
            ->first();
    }

    public function resolveOpenForDate(DateTimeInterface|string|null $date): ?FinancialPeriod
    {
        $period = $this->resolveForDate($date);

        if (! $period || $this->periodBlocksWrites($period)) {
            return null;
        }

        return $period;
    }

    public function ensureOperationDateIsWritable(DateTimeInterface|string|null $date, ?int $financialPeriodId = null): void
    {

        $period = $financialPeriodId
            ? FinancialPeriod::query()->find($financialPeriodId)
            : $this->resolveForDate($date);

        if (! $period) {
            return;
        }

        if ($this->periodBlocksWrites($period)) {
            dd([
                'financial_period_id' => __('dashboard.financial_periods.messages.period_is_closed', [
                    'period' => $period->name,
                ]),
            ]);
        }
    }

    public function ensureEntryIsWritable(Entry $entry): void
    {
        $operation = $entry->relationLoaded('operation')
            ? $entry->operation
            : $entry->operation()->first(['id', 'financial_period_id', 'date', 'is_locked']);

        if ($operation?->is_locked) {
            dd([
                'entry' => __('dashboard.financial_periods.messages.operation_locked'),
            ]);
        }

        $this->ensureOperationDateIsWritable(
            $entry->day_date ?: $operation?->date,
            $operation?->financial_period_id
        );
    }

    public function periodBlocksWrites(FinancialPeriod $period): bool
    {
        return in_array($period->status, [
            FinancialPeriodStatus::CLOSING,
            FinancialPeriodStatus::CLOSED,
            FinancialPeriodStatus::ARCHIVED,
        ], true);
    }

    protected function normalizeDate(DateTimeInterface|string|null $date): ?string
    {
        if ($date instanceof CarbonInterface) {
            return $date->toDateString();
        }

        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        if (blank($date)) {
            return null;
        }

        return Carbon::parse($date)->toDateString();
    }
}
