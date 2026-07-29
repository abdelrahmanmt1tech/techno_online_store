<?php

namespace App\Actions\Hr;

use App\Enums\Hr\AttendanceSource;
use App\Enums\Hr\AttendanceStatus;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Services\Hr\AttendanceScheduleResolver;
use App\Services\Hr\GeolocationService;
use App\Services\Hr\HrSettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttendanceCheckInAction
{
    public function __construct(
        private readonly AttendanceScheduleResolver $schedules,
        private readonly GeolocationService $geo,
        private readonly HrSettingsService $settings,
    ) {}

    /**
     * @param  array{latitude: float|string, longitude: float|string, accuracy?: float|string|null, user_agent?: string|null, ip_address?: string|null}  $payload
     */
    public function execute(HrEmployee $employee, array $payload): HrAttendanceRecord
    {
        if (! $employee->isOperationallyActive()) {
            throw ValidationException::withMessages([
                'employee' => __('hr.validation.employee_inactive'),
            ]);
        }

        $schedule = $this->schedules->resolveSchedule($employee);
        if (! $schedule) {
            throw ValidationException::withMessages([
                'schedule' => __('hr.validation.schedule_required'),
            ]);
        }

        $now = now();
        $window = $this->schedules->windowFor($schedule, $now);
        if (! $window['is_working_day']) {
            throw ValidationException::withMessages([
                'schedule' => __('hr.validation.not_working_day'),
            ]);
        }

        $location = $this->schedules->resolveLocation($employee);
        if (! $location) {
            throw ValidationException::withMessages([
                'location' => __('hr.validation.location_required'),
            ]);
        }

        $lat = (float) $payload['latitude'];
        $lon = (float) $payload['longitude'];
        $accuracy = isset($payload['accuracy']) ? (float) $payload['accuracy'] : null;

        $this->assertAccuracy($location, $accuracy);

        $distance = $this->geo->distanceMeters(
            $lat,
            $lon,
            (float) $location->latitude,
            (float) $location->longitude,
        );

        if ($distance > (int) $location->allowed_radius_meters) {
            throw ValidationException::withMessages([
                'location' => __('hr.validation.outside_geofence', [
                    'distance' => round($distance),
                    'radius' => $location->allowed_radius_meters,
                ]),
            ]);
        }

        /** @var Carbon $start */
        $start = $window['start'];
        /** @var Carbon $end */
        $end = $window['end'];

        if ($schedule->early_check_in_minutes !== null) {
            $earliest = $start->copy()->subMinutes((int) $schedule->early_check_in_minutes);
            if ($now->lt($earliest)) {
                throw ValidationException::withMessages([
                    'check_in' => __('hr.validation.too_early_check_in'),
                ]);
            }
        }

        $grace = $this->schedules->graceMinutes($employee, $schedule);
        $lateThreshold = $start->copy()->addMinutes($grace);
        $lateMinutes = $now->gt($lateThreshold) ? (int) $lateThreshold->diffInMinutes($now) : 0;
        $status = $lateMinutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present;

        return DB::connection('tenant')->transaction(function () use (
            $employee, $schedule, $location, $now, $start, $end, $lat, $lon, $accuracy, $distance, $lateMinutes, $status, $payload
        ) {
            $existing = HrAttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existing?->check_in_at) {
                throw ValidationException::withMessages([
                    'check_in' => __('hr.validation.already_checked_in'),
                ]);
            }

            $attrs = [
                'employee_id' => $employee->id,
                'attendance_date' => $now->toDateString(),
                'schedule_id' => $schedule->id,
                'attendance_location_id' => $location->id,
                'scheduled_start_at' => $start,
                'scheduled_end_at' => $end,
                'check_in_at' => $now,
                'check_in_latitude' => $lat,
                'check_in_longitude' => $lon,
                'check_in_accuracy' => $accuracy,
                'check_in_distance_meters' => round($distance, 2),
                'late_minutes' => $lateMinutes,
                'status' => $status,
                'source' => AttendanceSource::Employee,
                'ip_address' => $payload['ip_address'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
            ];

            if ($existing) {
                $existing->fill($attrs)->save();

                return $existing->fresh();
            }

            return HrAttendanceRecord::query()->create($attrs);
        });
    }

    private function assertAccuracy(mixed $location, ?float $accuracy): void
    {
        $settings = $this->settings->getOrCreate();
        $maxAccuracy = $location->minimum_accuracy_meters
            ?? ($settings->require_location_accuracy ? $settings->default_maximum_accuracy_meters : null);

        if ($maxAccuracy === null) {
            return;
        }

        if ($accuracy === null || $accuracy > (float) $maxAccuracy) {
            throw ValidationException::withMessages([
                'accuracy' => __('hr.validation.accuracy_too_low', ['max' => $maxAccuracy]),
            ]);
        }
    }
}
