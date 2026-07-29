<?php

namespace App\Services\Hr;

use App\Models\Tenant\HrAttendanceLocation;
use App\Models\Tenant\HrAttendanceSchedule;
use App\Models\Tenant\HrAttendanceScheduleDay;
use App\Models\Tenant\HrEmployee;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class AttendanceScheduleResolver
{
    public function __construct(private readonly HrSettingsService $settings) {}

    public function resolveSchedule(HrEmployee $employee): ?HrAttendanceSchedule
    {
        if ($employee->attendance_schedule_id) {
            $schedule = HrAttendanceSchedule::query()
                ->with('days')
                ->whereKey($employee->attendance_schedule_id)
                ->where('is_active', true)
                ->first();
            if ($schedule) {
                return $schedule;
            }
        }

        $default = $this->settings->defaultSchedule();
        if ($default) {
            $default->loadMissing('days');
        }

        return $default;
    }

    public function resolveLocation(HrEmployee $employee): ?HrAttendanceLocation
    {
        if ($employee->attendance_location_id) {
            $location = HrAttendanceLocation::query()
                ->whereKey($employee->attendance_location_id)
                ->where('is_active', true)
                ->first();
            if ($location) {
                return $location;
            }
        }

        if ($employee->branch_id) {
            $branchLocation = HrAttendanceLocation::query()
                ->where('branch_id', $employee->branch_id)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();
            if ($branchLocation) {
                return $branchLocation;
            }
        }

        return $this->settings->defaultLocation();
    }

    public function dayFor(HrAttendanceSchedule $schedule, CarbonInterface $date): ?HrAttendanceScheduleDay
    {
        $schedule->loadMissing('days');

        return $schedule->days->firstWhere('day_of_week', (int) $date->dayOfWeek);
    }

    /**
     * @return array{is_working_day: bool, start: ?Carbon, end: ?Carbon, day: ?HrAttendanceScheduleDay}
     */
    public function windowFor(HrAttendanceSchedule $schedule, CarbonInterface $date): array
    {
        $day = $this->dayFor($schedule, $date);
        if (! $day || ! $day->is_working_day || ! $day->start_time || ! $day->end_time) {
            return [
                'is_working_day' => false,
                'start' => null,
                'end' => null,
                'day' => $day,
            ];
        }

        $dateString = $date->toDateString();

        return [
            'is_working_day' => true,
            'start' => Carbon::parse($dateString.' '.$day->start_time),
            'end' => Carbon::parse($dateString.' '.$day->end_time),
            'day' => $day,
        ];
    }

    public function graceMinutes(HrEmployee $employee, HrAttendanceSchedule $schedule): int
    {
        if ($employee->custom_late_grace_minutes !== null) {
            return (int) $employee->custom_late_grace_minutes;
        }

        return (int) $schedule->late_grace_minutes;
    }
}
