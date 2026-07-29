<?php

namespace App\Services\Hr;

use App\Enums\Hr\AbsenceDeductionType;
use App\Enums\Hr\AttendanceStatus;
use App\Enums\Hr\LateDeductionType;
use App\Enums\Hr\SalaryType;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrSetting;
use App\Support\Erp\Decimal;
use Illuminate\Support\Collection;

final class PayrollDeductionCalculator
{
    public function __construct(private readonly HrSettingsService $settings) {}

    /**
     * @param  Collection<int, HrAttendanceRecord>  $records
     * @return array{
     *   present_days: int,
     *   late_days: int,
     *   absent_days: int,
     *   total_late_minutes: int,
     *   working_days_count: int,
     *   absence_deduction: string,
     *   late_deduction: string,
     *   details: array<string, mixed>
     * }
     */
    public function summarize(HrEmployee $employee, Collection $records, ?HrSetting $settings = null): array
    {
        $settings ??= $this->settings->getOrCreate();
        $workingDaysPerMonth = max(1, (int) $settings->working_days_per_month);

        $presentDays = 0;
        $lateDays = 0;
        $absentDays = 0;
        $totalLateMinutes = 0;

        foreach ($records as $record) {
            match ($record->status) {
                AttendanceStatus::Present => $presentDays++,
                AttendanceStatus::Late => tap(null, function () use (&$lateDays, &$presentDays, &$totalLateMinutes, $record) {
                    $lateDays++;
                    $presentDays++;
                    $totalLateMinutes += (int) $record->late_minutes;
                }),
                AttendanceStatus::Absent => $absentDays++,
                default => null,
            };
        }

        $absence = $this->absenceDeduction($employee, $absentDays, $settings, $workingDaysPerMonth);
        $late = $this->lateDeduction($employee, $lateDays, $totalLateMinutes, $settings);

        return [
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'total_late_minutes' => $totalLateMinutes,
            'working_days_count' => $workingDaysPerMonth,
            'absence_deduction' => $absence['amount'],
            'late_deduction' => $late['amount'],
            'details' => [
                'absence' => $absence,
                'late' => $late,
                'working_days_per_month' => $workingDaysPerMonth,
            ],
        ];
    }

    /**
     * @return array{amount: string, type: string, daily_rate?: string, days?: int, fixed?: string}
     */
    public function absenceDeduction(HrEmployee $employee, int $absentDays, HrSetting $settings, int $workingDaysPerMonth): array
    {
        $type = $employee->custom_absence_deduction_type
            ?? $settings->default_absence_deduction_type
            ?? AbsenceDeductionType::None;

        if ($absentDays < 1 || $type === AbsenceDeductionType::None) {
            return ['amount' => '0.00', 'type' => AbsenceDeductionType::None->value, 'days' => $absentDays];
        }

        if ($type === AbsenceDeductionType::FixedAmount) {
            $fixed = Decimal::money(
                $employee->custom_absence_deduction_value
                    ?? $settings->default_absence_fixed_amount
                    ?? '0'
            );
            $amount = Decimal::money(Decimal::mul($fixed, (string) $absentDays));

            return [
                'amount' => $amount,
                'type' => $type->value,
                'fixed' => $fixed,
                'days' => $absentDays,
            ];
        }

        // daily_rate
        $base = Decimal::money($employee->base_salary);
        if ($employee->salary_type === SalaryType::Daily) {
            $daily = $base;
        } else {
            $daily = Decimal::money(Decimal::div($base, (string) $workingDaysPerMonth));
        }
        $amount = Decimal::money(Decimal::mul($daily, (string) $absentDays));

        return [
            'amount' => $amount,
            'type' => AbsenceDeductionType::DailyRate->value,
            'daily_rate' => $daily,
            'days' => $absentDays,
        ];
    }

    /**
     * @return array{amount: string, type: string, late_days?: int, total_late_minutes?: int}
     */
    public function lateDeduction(HrEmployee $employee, int $lateDays, int $totalLateMinutes, HrSetting $settings): array
    {
        $type = $settings->default_late_deduction_type ?? LateDeductionType::None;
        if ($lateDays < 1 || $type === LateDeductionType::None) {
            return ['amount' => '0.00', 'type' => LateDeductionType::None->value];
        }

        $amount = '0.00';
        if ($type === LateDeductionType::FixedPerLateDay) {
            $fixed = Decimal::money($settings->default_late_fixed_amount ?? '0');
            $amount = Decimal::money(Decimal::mul($fixed, (string) $lateDays));
        } elseif ($type === LateDeductionType::PerMinute) {
            $perMinute = Decimal::money($settings->default_late_amount_per_minute ?? '0');
            $amount = Decimal::money(Decimal::mul($perMinute, (string) $totalLateMinutes));
            if ($settings->maximum_late_deduction_per_day !== null) {
                $capPerDay = Decimal::money($settings->maximum_late_deduction_per_day);
                $cap = Decimal::money(Decimal::mul($capPerDay, (string) max(1, $lateDays)));
                if (Decimal::cmp($amount, $cap, 2) > 0) {
                    $amount = $cap;
                }
            }
        }

        return [
            'amount' => $amount,
            'type' => $type->value,
            'late_days' => $lateDays,
            'total_late_minutes' => $totalLateMinutes,
        ];
    }
}
