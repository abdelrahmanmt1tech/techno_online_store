<?php

namespace App\Http\Controllers\Tenant\Hr;

use App\Actions\Hr\AttendanceCheckInAction;
use App\Actions\Hr\AttendanceCheckOutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Hr\AttendancePunchRequest;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Services\Hr\AttendanceScheduleResolver;
use App\Services\Hr\GeolocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class EmployeeAttendanceController extends Controller
{
    private function authorizeAttendanceRead(): void
    {
        $user = Auth::guard('tenant')->user();

        if (! $user) {
            abort(403);
        }

        if (! $user->can('hr.attendance.check_in') && ! $user->can('hr.attendance.check_out')) {
            abort(403);
        }
    }

    public function page(
        Request $request,
        AttendanceScheduleResolver $schedules,
    ): View {
        $this->authorizeAttendanceRead();

        $user = Auth::guard('tenant')->user();
        $employee = $this->currentEmployee();
        $now = now();

        $schedule = $employee ? $schedules->resolveSchedule($employee) : null;
        $location = $employee ? $schedules->resolveLocation($employee) : null;
        $window = $schedule ? $schedules->windowFor($schedule, $now) : null;
        $today = $employee
            ? HrAttendanceRecord::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->first()
            : null;

        $canCheckIn = (bool) $user?->can('hr.attendance.check_in');
        $canCheckOut = (bool) $user?->can('hr.attendance.check_out');
        $employeeActive = $employee?->isOperationallyActive() ?? false;

        // حالة العرض للواجهة — المنطق الأمني يبقى في الـ Actions
        $uiState = 'ready';
        if (! $employee) {
            $uiState = 'not_linked';
        } elseif (! $employeeActive) {
            $uiState = 'inactive';
        } elseif (! $schedule || ! $location) {
            $uiState = 'incomplete_settings';
        } elseif (! ($window['is_working_day'] ?? false)) {
            $uiState = 'day_off';
        } elseif ($today?->check_out_at) {
            $uiState = 'checked_out';
        } elseif ($today?->check_in_at) {
            $uiState = $today->late_minutes > 0 ? 'late' : 'checked_in';
        } else {
            $uiState = 'not_checked_in';
        }

        return view('hr.attendance', [
            'employee' => $employee,
            'schedule' => $schedule,
            'location' => $location,
            'window' => $window,
            'today' => $today,
            'now' => $now,
            'apiBase' => url('/app/hr/attendance'),
            'locale' => app()->getLocale(),
            'canCheckIn' => $canCheckIn,
            'canCheckOut' => $canCheckOut,
            'employeeActive' => $employeeActive,
            'uiState' => $uiState,
            'i18n' => [
                'locating' => __('hr.labels.locating'),
                'locationReady' => __('hr.labels.location_ready'),
                'accuracy' => __('hr.labels.location_accuracy'),
                'inside' => __('hr.labels.inside_geofence'),
                'outside' => __('hr.labels.outside_geofence'),
                'distanceHint' => __('hr.labels.distance_hint'),
                'successIn' => __('hr.notifications.checked_in'),
                'successOut' => __('hr.notifications.checked_out'),
                'sending' => __('hr.labels.sending'),
                'confirmOut' => __('hr.labels.confirm_check_out'),
                'geoUnsupported' => __('hr.validation.geolocation_unsupported'),
                'geoDenied' => __('hr.validation.geolocation_denied'),
                'geoUnavailable' => __('hr.validation.geolocation_unavailable'),
                'geoTimeout' => __('hr.validation.geolocation_timeout'),
                'geoRequired' => __('hr.validation.geolocation_required'),
                'httpsRequired' => __('hr.validation.https_required'),
                'error' => __('hr.notifications.error'),
                'checkedIn' => __('hr.labels.checked_in'),
                'checkedOut' => __('hr.labels.checked_out'),
                'late' => __('hr.labels.late'),
                'statusLabels' => [
                    'present' => __('hr.attendance_statuses.present'),
                    'late' => __('hr.attendance_statuses.late'),
                    'absent' => __('hr.attendance_statuses.absent'),
                    'incomplete' => __('hr.attendance_statuses.incomplete'),
                    'day_off' => __('hr.attendance_statuses.day_off'),
                    'manual' => __('hr.attendance_statuses.manual'),
                ],
            ],
        ]);
    }

    public function status(AttendanceScheduleResolver $schedules): JsonResponse
    {
        $this->authorizeAttendanceRead();
        $employee = $this->currentEmployeeOrFail();

        if (! $employee->isOperationallyActive()) {
            abort(403, __('hr.validation.employee_inactive'));
        }

        $now = now();
        $schedule = $schedules->resolveSchedule($employee);
        $location = $schedules->resolveLocation($employee);
        $window = $schedule ? $schedules->windowFor($schedule, $now) : null;
        $today = HrAttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();

        // لا نُرجع إحداثيات موقع العمل — المسافة عبر /distance فقط
        return response()->json([
            'data' => [
                'employee' => [
                    'id' => $employee->id,
                    'full_name' => $employee->full_name,
                    'employee_number' => $employee->employee_number,
                ],
                'schedule' => $schedule ? [
                    'id' => $schedule->id,
                    'name' => $schedule->name,
                    'late_grace_minutes' => $schedule->late_grace_minutes,
                    'allow_check_out_outside_location' => $schedule->allow_check_out_outside_location,
                ] : null,
                'location' => $location ? [
                    'id' => $location->id,
                    'name' => $location->name,
                    'allowed_radius_meters' => (int) $location->allowed_radius_meters,
                    'maximum_accuracy_meters' => $location->maximum_accuracy_meters,
                ] : null,
                'window' => [
                    'is_working_day' => (bool) ($window['is_working_day'] ?? false),
                    'start' => $window['start']?->toIso8601String(),
                    'end' => $window['end']?->toIso8601String(),
                ],
                'today' => $today ? [
                    'status' => $today->status->value,
                    'status_label' => $today->status->label(),
                    'check_in_at' => optional($today->check_in_at)?->toIso8601String(),
                    'check_out_at' => optional($today->check_out_at)?->toIso8601String(),
                    'late_minutes' => $today->late_minutes,
                    'worked_minutes' => $today->worked_minutes,
                    'early_leave_minutes' => $today->early_leave_minutes,
                ] : null,
                'server_now' => $now->toIso8601String(),
            ],
        ]);
    }

    public function checkIn(AttendancePunchRequest $request, AttendanceCheckInAction $action): JsonResponse
    {
        $employee = $this->currentEmployeeOrFail();

        if (! $employee->isOperationallyActive()) {
            abort(403, __('hr.validation.employee_inactive'));
        }

        if (! $request->user('tenant')?->can('hr.attendance.check_in')) {
            abort(403);
        }

        $record = $action->execute($employee, [
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'accuracy' => $request->validated('accuracy'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json(['data' => $this->recordPayload($record)], 201);
    }

    public function checkOut(AttendancePunchRequest $request, AttendanceCheckOutAction $action): JsonResponse
    {
        $employee = $this->currentEmployeeOrFail();

        if (! $employee->isOperationallyActive()) {
            abort(403, __('hr.validation.employee_inactive'));
        }

        if (! $request->user('tenant')?->can('hr.attendance.check_out')) {
            abort(403);
        }

        $record = $action->execute($employee, [
            'latitude' => $request->validated('latitude'),
            'longitude' => $request->validated('longitude'),
            'accuracy' => $request->validated('accuracy'),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
        ]);

        return response()->json(['data' => $this->recordPayload($record)]);
    }

    public function distance(Request $request, AttendanceScheduleResolver $schedules, GeolocationService $geo): JsonResponse
    {
        $this->authorizeAttendanceRead();
        $employee = $this->currentEmployeeOrFail();

        if (! $employee->isOperationallyActive()) {
            abort(403, __('hr.validation.employee_inactive'));
        }

        $location = $schedules->resolveLocation($employee);
        if (! $location) {
            return response()->json([
                'data' => ['has_location' => false],
            ]);
        }

        $lat = (float) $request->query('latitude');
        $lon = (float) $request->query('longitude');

        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return response()->json([
                'message' => __('hr.validation.geolocation_required'),
            ], 422);
        }

        $distance = $geo->distanceMeters($lat, $lon, (float) $location->latitude, (float) $location->longitude);

        return response()->json([
            'data' => [
                'has_location' => true,
                'distance_meters' => round($distance, 1),
                'allowed_radius_meters' => (int) $location->allowed_radius_meters,
                'inside' => $distance <= (int) $location->allowed_radius_meters,
            ],
        ]);
    }

    private function currentEmployee(): ?HrEmployee
    {
        $user = Auth::guard('tenant')->user();
        if (! $user) {
            return null;
        }

        return HrEmployee::query()
            ->where('user_id', $user->id)
            ->first();
    }

    private function currentEmployeeOrFail(): HrEmployee
    {
        $employee = $this->currentEmployee();
        if (! $employee) {
            abort(403, __('hr.validation.user_not_linked_employee'));
        }

        return $employee;
    }

    /**
     * @return array<string, mixed>
     */
    private function recordPayload(HrAttendanceRecord $record): array
    {
        return [
            'id' => $record->id,
            'status' => $record->status->value,
            'status_label' => $record->status->label(),
            'check_in_at' => optional($record->check_in_at)?->toIso8601String(),
            'check_out_at' => optional($record->check_out_at)?->toIso8601String(),
            'late_minutes' => $record->late_minutes,
            'early_leave_minutes' => $record->early_leave_minutes,
            'worked_minutes' => $record->worked_minutes,
            'check_in_distance_meters' => $record->check_in_distance_meters,
            'check_out_distance_meters' => $record->check_out_distance_meters,
        ];
    }
}
