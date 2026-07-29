<?php

namespace App\Actions\Hr;

use App\Enums\Hr\AttendanceSource;
use App\Enums\Hr\AttendanceStatus;
use App\Enums\Hr\EmploymentStatus;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Services\Hr\AttendanceScheduleResolver;
use App\Services\Hr\HrSettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class MarkAbsentEmployeesAction
{
    public function __construct(
        private readonly AttendanceScheduleResolver $schedules,
        private readonly HrSettingsService $settings,
    ) {}

    /**
     * Idempotent absent marking for a given calendar date (defaults to today).
     *
     * @return int Number of absent records created/updated
     */
    public function execute(?Carbon $date = null): int
    {
        $settings = $this->settings->getOrCreate();
        if (! $settings->auto_mark_absent) {
            return 0;
        }

        $date ??= now();
        $count = 0;

        $employees = HrEmployee::query()
            ->where('is_active', true)
            ->where('employment_status', EmploymentStatus::Active)
            ->get();

        foreach ($employees as $employee) {
            $schedule = $this->schedules->resolveSchedule($employee);
            if (! $schedule) {
                continue;
            }

            $window = $this->schedules->windowFor($schedule, $date);
            if (! $window['is_working_day'] || ! $window['end']) {
                continue;
            }

            // Only after scheduled end + 30 min grace margin
            if (now()->lt($window['end']->copy()->addMinutes(30))) {
                continue;
            }

            $created = DB::connection('tenant')->transaction(function () use ($employee, $schedule, $window, $date) {
                $record = HrAttendanceRecord::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('attendance_date', $date->toDateString())
                    ->lockForUpdate()
                    ->first();

                if ($record?->check_in_at) {
                    return false;
                }

                if ($record && $record->status === AttendanceStatus::Absent) {
                    return false;
                }

                $attrs = [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date->toDateString(),
                    'schedule_id' => $schedule->id,
                    'scheduled_start_at' => $window['start'],
                    'scheduled_end_at' => $window['end'],
                    'status' => AttendanceStatus::Absent,
                    'source' => AttendanceSource::System,
                    'late_minutes' => 0,
                    'early_leave_minutes' => 0,
                ];

                if ($record) {
                    $record->fill($attrs)->save();
                } else {
                    HrAttendanceRecord::query()->create($attrs);
                }

                return true;
            });

            if ($created) {
                $count++;
            }
        }

        return $count;
    }
}
