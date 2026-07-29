<?php

namespace App\Actions\Hr;

use App\Enums\Hr\AttendanceStatus;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Services\Hr\AttendanceScheduleResolver;
use App\Services\Hr\GeolocationService;
use App\Services\Hr\HrSettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AttendanceCheckOutAction
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

        $now = now();

        return DB::connection('tenant')->transaction(function () use ($employee, $payload, $now) {
            /** @var HrAttendanceRecord|null $record */
            $record = HrAttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $record || ! $record->check_in_at) {
                throw ValidationException::withMessages([
                    'check_out' => __('hr.validation.check_in_required'),
                ]);
            }

            if ($record->check_out_at) {
                throw ValidationException::withMessages([
                    'check_out' => __('hr.validation.already_checked_out'),
                ]);
            }

            $schedule = $this->schedules->resolveSchedule($employee);
            $location = $record->attendance_location_id
                ? $record->location
                : $this->schedules->resolveLocation($employee);

            $lat = (float) $payload['latitude'];
            $lon = (float) $payload['longitude'];
            $accuracy = isset($payload['accuracy']) ? (float) $payload['accuracy'] : null;
            $distance = null;

            $allowOutside = (bool) ($schedule?->allow_check_out_outside_location);

            if (! $allowOutside) {
                if (! $location) {
                    throw ValidationException::withMessages([
                        'location' => __('hr.validation.location_required'),
                    ]);
                }

                $settings = $this->settings->getOrCreate();
                $maxAccuracy = $location->maximum_accuracy_meters
                    ?? ($settings->require_location_accuracy ? $settings->default_maximum_accuracy_meters : null);

                if ($maxAccuracy !== null && ($accuracy === null || $accuracy <= 0)) {
                    throw ValidationException::withMessages([
                        'accuracy' => __('hr.validation.accuracy_too_low', ['max' => $maxAccuracy]),
                    ]);
                }

                if ($maxAccuracy !== null && ($accuracy === null || $accuracy > (float) $maxAccuracy)) {
                    throw ValidationException::withMessages([
                        'accuracy' => __('hr.validation.accuracy_too_low', ['max' => $maxAccuracy]),
                    ]);
                }

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
            } elseif ($location) {
                $distance = $this->geo->distanceMeters(
                    $lat,
                    $lon,
                    (float) $location->latitude,
                    (float) $location->longitude,
                );
            }

            $worked = max(0, (int) $record->check_in_at->diffInMinutes($now));
            $earlyLeave = 0;
            if ($record->scheduled_end_at && $now->lt($record->scheduled_end_at)) {
                $earlyLeave = (int) $now->diffInMinutes($record->scheduled_end_at);
            }

            $status = $record->late_minutes > 0 ? AttendanceStatus::Late : AttendanceStatus::Present;

            $record->fill([
                'check_out_at' => $now,
                'check_out_latitude' => $lat,
                'check_out_longitude' => $lon,
                'check_out_accuracy' => $accuracy,
                'check_out_distance_meters' => $distance !== null ? round($distance, 2) : null,
                'worked_minutes' => $worked,
                'early_leave_minutes' => $earlyLeave,
                'status' => $status,
                'ip_address' => $payload['ip_address'] ?? $record->ip_address,
                'user_agent' => $payload['user_agent'] ?? $record->user_agent,
            ])->save();

            return $record->fresh();
        });
    }
}
