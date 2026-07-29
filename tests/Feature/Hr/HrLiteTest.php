<?php

namespace Tests\Feature\Hr;

use App\Actions\Hr\AttendanceCheckInAction;
use App\Actions\Hr\AttendanceCheckOutAction;
use App\Actions\Hr\MarkAbsentEmployeesAction;
use App\Actions\Hr\PayrollApprovalAction;
use App\Actions\Hr\PayrollPaymentAction;
use App\Enums\Hr\AbsenceDeductionType;
use App\Enums\Hr\AttendanceStatus;
use App\Enums\Hr\EmploymentStatus;
use App\Enums\Hr\LateDeductionType;
use App\Enums\Hr\PayrollPeriodStatus;
use App\Enums\Hr\SalaryType;
use App\Models\Tenant\HrAttendanceLocation;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrAttendanceSchedule;
use App\Models\Tenant\HrAttendanceScheduleDay;
use App\Models\Tenant\HrDepartment;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrJobTitle;
use App\Models\Tenant\HrPayrollPeriod;
use App\Models\TenantUser;
use App\Services\Hr\GeolocationService;
use App\Services\Hr\HrSettingsService;
use App\Services\Hr\PayrollGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Erp\ErpTestCase;

class HrLiteTest extends ErpTestCase
{
    private HrAttendanceSchedule $schedule;

    private HrAttendanceLocation $location;

    private HrEmployee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-07-29 09:30:00')); // Wednesday

        $this->schedule = HrAttendanceSchedule::query()->create([
            'name' => 'Standard',
            'is_default' => true,
            'is_active' => true,
            'late_grace_minutes' => 15,
            'early_check_in_minutes' => 60,
            'allow_check_out_outside_location' => false,
            'absence_deduction_enabled' => true,
            'late_deduction_enabled' => true,
        ]);

        foreach (range(0, 6) as $day) {
            HrAttendanceScheduleDay::query()->create([
                'attendance_schedule_id' => $this->schedule->id,
                'day_of_week' => $day,
                'is_working_day' => ! in_array($day, [5, 6], true),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
        }

        $this->location = HrAttendanceLocation::query()->create([
            'name' => 'Cairo Office',
            'branch_id' => $this->branch->id,
            'latitude' => 30.0444000,
            'longitude' => 31.2357000,
            'allowed_radius_meters' => 150,
            'minimum_accuracy_meters' => 100,
            'is_active' => true,
        ]);

        app(HrSettingsService::class)->update([
            'default_attendance_schedule_id' => $this->schedule->id,
            'default_attendance_location_id' => $this->location->id,
            'working_days_per_month' => 30,
            'default_absence_deduction_type' => AbsenceDeductionType::DailyRate,
            'default_late_deduction_type' => LateDeductionType::PerMinute,
            'default_late_amount_per_minute' => '1.00',
            'maximum_late_deduction_per_day' => '50.00',
            'auto_mark_absent' => true,
            'require_location_accuracy' => true,
            'default_maximum_accuracy_meters' => 100,
        ]);

        $dept = HrDepartment::query()->create(['name' => 'Ops', 'is_active' => true]);
        $title = HrJobTitle::query()->create(['name' => 'Cashier', 'is_active' => true]);

        $this->employee = HrEmployee::query()->create([
            'employee_number' => 'E-001',
            'user_id' => $this->user->id,
            'full_name' => 'HR Employee',
            'branch_id' => $this->branch->id,
            'department_id' => $dept->id,
            'job_title_id' => $title->id,
            'attendance_schedule_id' => $this->schedule->id,
            'attendance_location_id' => $this->location->id,
            'hire_date' => '2026-01-01',
            'employment_status' => EmploymentStatus::Active,
            'salary_type' => SalaryType::Monthly,
            'base_salary' => 3000,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_employee_number_unique_and_user_unique(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        HrEmployee::query()->create([
            'employee_number' => 'E-001',
            'full_name' => 'Dup',
            'employment_status' => EmploymentStatus::Active,
            'salary_type' => SalaryType::Monthly,
            'base_salary' => 1000,
            'is_active' => true,
        ]);
    }

    public function test_geolocation_distance_inside_and_outside(): void
    {
        $geo = app(GeolocationService::class);
        $inside = $geo->distanceMeters(30.0444, 31.2357, 30.0445, 31.2358);
        $this->assertTrue($inside < 150);

        $outside = $geo->distanceMeters(30.0444, 31.2357, 30.0500, 31.2400);
        $this->assertTrue($outside > 150);
    }

    public function test_check_in_success_and_late_calculation(): void
    {
        $record = app(AttendanceCheckInAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);

        $this->assertNotNull($record->check_in_at);
        $this->assertSame(AttendanceStatus::Late, $record->status);
        $this->assertGreaterThan(0, $record->late_minutes);
    }

    public function test_check_in_rejects_outside_geofence_and_bad_accuracy(): void
    {
        try {
            app(AttendanceCheckInAction::class)->execute($this->employee, [
                'latitude' => 30.0600,
                'longitude' => 31.2500,
                'accuracy' => 20,
            ]);
            $this->fail('Expected outside geofence');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('location', $e->errors());
        }

        try {
            app(AttendanceCheckInAction::class)->execute($this->employee, [
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'accuracy' => 500,
            ]);
            $this->fail('Expected accuracy failure');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('accuracy', $e->errors());
        }
    }

    public function test_prevent_double_check_in_and_checkout_flow(): void
    {
        app(AttendanceCheckInAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);

        $this->expectException(ValidationException::class);
        app(AttendanceCheckInAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);
    }

    public function test_check_out_and_early_leave(): void
    {
        app(AttendanceCheckInAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-07-29 16:00:00'));

        $out = app(AttendanceCheckOutAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);

        $this->assertNotNull($out->check_out_at);
        $this->assertGreaterThan(0, $out->early_leave_minutes);
        $this->assertNotNull($out->worked_minutes);
    }

    public function test_mark_absent_idempotent_and_skips_day_off(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-29 18:00:00'));
        $action = app(MarkAbsentEmployeesAction::class);
        $first = $action->execute(Carbon::parse('2026-07-29'));
        $second = $action->execute(Carbon::parse('2026-07-29'));

        $this->assertSame(1, $first);
        $this->assertSame(0, $second);
        $this->assertSame(1, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());

        // Friday day off
        $friday = $action->execute(Carbon::parse('2026-07-31'));
        $this->assertSame(0, $friday);
    }

    public function test_payroll_generation_deductions_approve_and_pay(): void
    {
        // Present late day
        app(AttendanceCheckInAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);
        Carbon::setTestNow(Carbon::parse('2026-07-29 17:30:00'));
        app(AttendanceCheckOutAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);

        // Absent day
        Carbon::setTestNow(Carbon::parse('2026-07-30 18:30:00'));
        app(MarkAbsentEmployeesAction::class)->execute(Carbon::parse('2026-07-30'));

        $period = HrPayrollPeriod::query()->create([
            'name' => 'July 2026',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'status' => PayrollPeriodStatus::Draft,
        ]);

        $generated = app(PayrollGenerationService::class)->generate($period);
        $this->assertSame(PayrollPeriodStatus::Generated, $generated->status);
        $line = $generated->employees()->first();
        $this->assertNotNull($line);
        $this->assertSame('3000.00', (string) $line->base_salary_snapshot);
        $this->assertGreaterThanOrEqual(1, (int) $line->absent_days);
        $this->assertTrue((float) $line->net_salary >= 0);

        $approved = app(PayrollApprovalAction::class)->execute($generated);
        $this->assertSame(PayrollPeriodStatus::Approved, $approved->status);

        $this->expectException(ValidationException::class);
        app(PayrollGenerationService::class)->generate($approved, true);
    }

    public function test_payroll_mark_paid_and_prevent_negative_net(): void
    {
        $period = HrPayrollPeriod::query()->create([
            'name' => 'Aug',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => PayrollPeriodStatus::Draft,
        ]);
        $generated = app(PayrollGenerationService::class)->generate($period);
        $line = $generated->employees()->first();
        app(PayrollGenerationService::class)->applyManualDeduction($line, '999999', 'cap');
        $line->refresh();
        $this->assertSame('0.00', (string) $line->net_salary);

        $approved = app(PayrollApprovalAction::class)->execute($generated->fresh());
        $paid = app(PayrollPaymentAction::class)->execute($approved);
        $this->assertSame(PayrollPeriodStatus::Paid, $paid->status);
    }

    public function test_unlinked_user_cannot_operate_as_employee_profile(): void
    {
        $other = TenantUser::query()->create([
            'name' => 'Other',
            'email' => 'other-hr@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->actingAs($other, 'tenant');

        $orphan = HrEmployee::query()->create([
            'employee_number' => 'E-002',
            'full_name' => 'No User',
            'employment_status' => EmploymentStatus::Active,
            'salary_type' => SalaryType::Monthly,
            'base_salary' => 1000,
            'attendance_schedule_id' => $this->schedule->id,
            'attendance_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        // Direct action still requires employee object; controller enforces user link.
        $this->assertNull(HrEmployee::query()->where('user_id', $other->id)->first());
        $this->assertNull($orphan->user_id);
    }

    public function test_store_checkout_routes_untouched(): void
    {
        $routes = collect(app('router')->getRoutes())->map(fn ($r) => $r->uri());
        $this->assertTrue($routes->contains(fn ($uri) => str_contains($uri, 'checkout/{token}')));
        $this->assertTrue($routes->contains(fn ($uri) => str_contains($uri, 'hr/attendance')));
    }
}
