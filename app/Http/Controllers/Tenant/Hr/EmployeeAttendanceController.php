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
    public function page(
        Request $request,
        AttendanceScheduleResolver $schedules,
        GeolocationService $geo,
    ): View {
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

        return view('hr.attendance', [
            'employee' => $employee,
            'schedule' => $schedule,
            'location' => $location,
            'window' => $window,
            'today' => $today,
            'now' => $now,
            'apiBase' => url('/app/hr/attendance'),
            'locale' => app()->getLocale(),
        ]);
    }

    public function status(AttendanceScheduleResolver $schedules): JsonResponse
    {
        $employee = $this->currentEmployeeOrFail();
        $now = now();
        $schedule = $schedules->resolveSchedule($employee);
        $location = $schedules->resolveLocation($employee);
        $window = $schedule ? $schedules->windowFor($schedule, $now) : null;
        $today = HrAttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();

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
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'allowed_radius_meters' => (int) $location->allowed_radius_meters,
                    'minimum_accuracy_meters' => $location->minimum_accuracy_meters,
                ] : null,
                'window' => [
                    'is_working_day' => (bool) ($window['is_working_day'] ?? false),
                    'start' => $window['start']?->toIso8601String(),
                    'end' => $window['end']?->toIso8601String(),
                ],
                'today' => $today ? [
                    'status' => $today->status->value,
                    'check_in_at' => optional($today->check_in_at)?->toIso8601String(),
                    'check_out_at' => optional($today->check_out_at)?->toIso8601String(),
                    'late_minutes' => $today->late_minutes,
                ] : null,
                'server_now' => $now->toIso8601String(),
            ],
        ]);
    }

    public function checkIn(AttendancePunchRequest $request, AttendanceCheckInAction $action): JsonResponse
    {
        $employee = $this->currentEmployeeOrFail();
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
        $employee = $this->currentEmployeeOrFail();
        $location = $schedules->resolveLocation($employee);
        if (! $location) {
            return response()->json([
                'data' => ['has_location' => false],
            ]);
        }

        $lat = (float) $request->query('latitude');
        $lon = (float) $request->query('longitude');
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
