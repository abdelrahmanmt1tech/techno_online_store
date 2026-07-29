<?php

namespace App\Services\Hr;

use App\Enums\Hr\AbsenceDeductionType;
use App\Enums\Hr\LateDeductionType;
use App\Models\Tenant\HrAttendanceSchedule;
use App\Models\Tenant\HrAttendanceLocation;
use App\Models\Tenant\HrSetting;
use Illuminate\Validation\ValidationException;

final class HrSettingsService
{
    public function getOrCreate(): HrSetting
    {
        $settings = HrSetting::query()->first();
        if ($settings) {
            return $settings;
        }

        return HrSetting::query()->create([
            'working_days_per_month' => 30,
            'payroll_day_of_month' => 1,
            'default_absence_deduction_type' => AbsenceDeductionType::DailyRate,
            'default_late_deduction_type' => LateDeductionType::None,
            'auto_mark_absent' => true,
            'require_location_accuracy' => false,
        ]);
    }

    public function update(array $data): HrSetting
    {
        $settings = $this->getOrCreate();

        $workingDays = (int) ($data['working_days_per_month'] ?? $settings->working_days_per_month);
        if ($workingDays < 1) {
            throw ValidationException::withMessages([
                'working_days_per_month' => __('hr.validation.working_days_positive'),
            ]);
        }

        $settings->fill($data);
        $settings->save();

        return $settings->fresh();
    }

    public function defaultSchedule(): ?HrAttendanceSchedule
    {
        $settings = $this->getOrCreate();
        if ($settings->default_attendance_schedule_id) {
            return HrAttendanceSchedule::query()
                ->whereKey($settings->default_attendance_schedule_id)
                ->where('is_active', true)
                ->first();
        }

        return HrAttendanceSchedule::query()
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public function defaultLocation(): ?HrAttendanceLocation
    {
        $settings = $this->getOrCreate();
        if (! $settings->default_attendance_location_id) {
            return null;
        }

        return HrAttendanceLocation::query()
            ->whereKey($settings->default_attendance_location_id)
            ->where('is_active', true)
            ->first();
    }
}
