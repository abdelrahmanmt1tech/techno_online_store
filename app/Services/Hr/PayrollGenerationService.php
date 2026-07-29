<?php

namespace App\Services\Hr;

use App\Enums\Hr\EmploymentStatus;
use App\Enums\Hr\PayrollPeriodStatus;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrPayrollEmployee;
use App\Models\Tenant\HrPayrollPeriod;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PayrollGenerationService
{
    public function __construct(
        private readonly PayrollDeductionCalculator $deductions,
        private readonly HrSettingsService $settings,
    ) {}

    public function generate(HrPayrollPeriod $period, bool $rebuild = false): HrPayrollPeriod
    {
        if ($period->isLocked()) {
            throw ValidationException::withMessages([
                'status' => __('hr.validation.payroll_locked'),
            ]);
        }

        if ($period->status === PayrollPeriodStatus::Cancelled) {
            throw ValidationException::withMessages([
                'status' => __('hr.validation.payroll_cancelled'),
            ]);
        }

        if ($period->status === PayrollPeriodStatus::Generated && ! $rebuild) {
            throw ValidationException::withMessages([
                'status' => __('hr.validation.payroll_already_generated'),
            ]);
        }

        $this->assertNoOverlap($period);

        return DB::connection('tenant')->transaction(function () use ($period) {
            /** @var HrPayrollPeriod $locked */
            $locked = HrPayrollPeriod::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();

            $locked->employees()->delete();

            $settings = $this->settings->getOrCreate();
            $employees = HrEmployee::query()
                ->where('is_active', true)
                ->where('employment_status', EmploymentStatus::Active)
                ->orderBy('employee_number')
                ->get();

            foreach ($employees as $employee) {
                $records = HrAttendanceRecord::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('attendance_date', '>=', $locked->start_date)
                    ->whereDate('attendance_date', '<=', $locked->end_date)
                    ->get();

                $summary = $this->deductions->summarize($employee, $records, $settings);
                $manual = '0.00';
                $totalDeductions = Decimal::money(Decimal::add(
                    Decimal::add($summary['absence_deduction'], $summary['late_deduction']),
                    $manual
                ));
                $net = Decimal::money(Decimal::sub(Decimal::money($employee->base_salary), $totalDeductions));
                if (Decimal::isNegative($net)) {
                    $net = '0.00';
                }

                HrPayrollEmployee::query()->create([
                    'payroll_period_id' => $locked->id,
                    'employee_id' => $employee->id,
                    'base_salary_snapshot' => Decimal::money($employee->base_salary),
                    'salary_type_snapshot' => $employee->salary_type,
                    'working_days_count' => $summary['working_days_count'],
                    'present_days' => $summary['present_days'],
                    'late_days' => $summary['late_days'],
                    'absent_days' => $summary['absent_days'],
                    'total_late_minutes' => $summary['total_late_minutes'],
                    'absence_deduction' => $summary['absence_deduction'],
                    'late_deduction' => $summary['late_deduction'],
                    'manual_deduction' => $manual,
                    'manual_deduction_reason' => null,
                    'total_deductions' => $totalDeductions,
                    'net_salary' => $net,
                    'calculation_details' => $summary['details'],
                    'status' => 'generated',
                ]);
            }

            $locked->status = PayrollPeriodStatus::Generated;
            $locked->generated_at = now();
            $locked->save();

            return $locked->fresh(['employees']);
        });
    }

    public function applyManualDeduction(HrPayrollEmployee $line, string $amount, ?string $reason = null): HrPayrollEmployee
    {
        $period = $line->period;
        if ($period->isLocked() || $period->status === PayrollPeriodStatus::Cancelled) {
            throw ValidationException::withMessages([
                'status' => __('hr.validation.payroll_locked'),
            ]);
        }

        $manual = Decimal::money($amount);
        if (Decimal::isNegative($manual)) {
            throw ValidationException::withMessages([
                'manual_deduction' => __('hr.validation.manual_deduction_positive'),
            ]);
        }

        $total = Decimal::money(Decimal::add(
            Decimal::add((string) $line->absence_deduction, (string) $line->late_deduction),
            $manual
        ));
        $net = Decimal::money(Decimal::sub((string) $line->base_salary_snapshot, $total));
        if (Decimal::isNegative($net)) {
            $net = '0.00';
        }

        $line->fill([
            'manual_deduction' => $manual,
            'manual_deduction_reason' => $reason,
            'total_deductions' => $total,
            'net_salary' => $net,
        ])->save();

        return $line->fresh();
    }

    private function assertNoOverlap(HrPayrollPeriod $period): void
    {
        $overlap = HrPayrollPeriod::query()
            ->whereKeyNot($period->id)
            ->whereNotIn('status', [PayrollPeriodStatus::Cancelled->value])
            ->whereDate('start_date', '<=', $period->end_date)
            ->whereDate('end_date', '>=', $period->start_date)
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'dates' => __('hr.validation.payroll_period_overlap'),
            ]);
        }
    }
}
