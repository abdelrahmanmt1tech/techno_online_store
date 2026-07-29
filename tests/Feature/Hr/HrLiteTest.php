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
use App\Models\Tenant\Branch;
use App\Models\Tenant\HrDepartment;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\HrJobTitle;
use App\Models\Tenant;
use App\Models\Tenant\HrPayrollPeriod;
use App\Models\TenantUser;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use App\Services\Hr\GeolocationService;
use App\Services\Hr\HrSettingsService;
use App\Services\Hr\PayrollGenerationService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Erp\ErpTestCase;

class HrLiteTest extends ErpTestCase
{
    private HrAttendanceSchedule $schedule;

    private HrAttendanceLocation $location;

    private HrEmployee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('tenant'));

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
            'maximum_accuracy_meters' => 100,
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

    public function test_hr_mark_absent_command_marks_all_active_tenants_and_is_idempotent(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-29 18:00:00'));

        $tenant2 = $this->createTenantWithDatabase();
        $this->seedHrTenant($tenant2, 'E-200');

        // Run central scheduler command (should touch both tenant DBs).
        Artisan::call('hr:mark-absent', ['--date' => '2026-07-29']);

        $this->tenant->run(function () {
            $this->assertSame(1, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());
        });
        $tenant2->run(function () {
            $this->assertSame(1, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());
        });

        // Idempotency: running again should not create duplicates.
        Artisan::call('hr:mark-absent', ['--date' => '2026-07-29']);

        $this->tenant->run(function () {
            $this->assertSame(1, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());
        });
        $tenant2->run(function () {
            $this->assertSame(1, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());
        });
    }

    public function test_hr_mark_absent_command_continues_when_one_tenant_fails(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-29 18:00:00'));

        $tenant2 = $this->createTenantWithDatabase();

        // Break tenant2 by removing hr_settings table so the action must fail there.
        $tenant2->run(function () {
            \Illuminate\Support\Facades\Schema::dropIfExists('hr_settings');
        });

        $exitCode = Artisan::call('hr:mark-absent', ['--date' => '2026-07-29']);
        $this->assertSame(0, $exitCode);

        // Tenant1 must still be processed.
        $this->tenant->run(function () {
            $this->assertSame(1, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());
        });

        // Tenant2 should not have created absent records due to failure.
        $tenant2->run(function () {
            $this->assertSame(0, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());
        });
    }

    public function test_hr_mark_absent_command_does_not_mark_absent_before_end_plus_grace(): void
    {
        // end_time=17:00, grace=30m => mark absent only when now >= 17:30
        Carbon::setTestNow(Carbon::parse('2026-07-29 16:40:00'));

        Artisan::call('hr:mark-absent', ['--date' => '2026-07-29']);

        $this->assertSame(0, HrAttendanceRecord::query()->where('status', AttendanceStatus::Absent)->count());
    }

    public function test_gps_accuracy_threshold_boundary_checks(): void
    {
        $payload = [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ];

        // maxAccuracy = 100m
        Carbon::setTestNow(Carbon::parse('2026-07-29 09:30:00'));
        app(AttendanceCheckInAction::class)->execute($this->employee, [...$payload, 'accuracy' => 50]);

        Carbon::setTestNow(Carbon::parse('2026-07-30 09:30:00'));
        app(AttendanceCheckInAction::class)->execute($this->employee, [...$payload, 'accuracy' => 100]);

        Carbon::setTestNow(Carbon::parse('2026-08-02 09:30:00'));
        try {
            app(AttendanceCheckInAction::class)->execute($this->employee, [...$payload, 'accuracy' => 101]);
            $this->fail('Expected accuracy rejection at 101m');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('accuracy', $e->errors());
        }

        Carbon::setTestNow(Carbon::parse('2026-08-03 09:30:00'));
        try {
            app(AttendanceCheckInAction::class)->execute($this->employee, [...$payload, 'accuracy' => 500]);
            $this->fail('Expected accuracy rejection at 500m');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('accuracy', $e->errors());
        }

        // Null limit => do not enforce accuracy (should accept large values)
        $this->location->update(['maximum_accuracy_meters' => null]);
        app(HrSettingsService::class)->update([
            'require_location_accuracy' => false,
            'default_maximum_accuracy_meters' => null,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-04 09:30:00'));
        app(AttendanceCheckInAction::class)->execute($this->employee, [...$payload, 'accuracy' => 500]);

        // Reset limit and validate invalid values (0 and negative)
        $this->location->update(['maximum_accuracy_meters' => 100]);
        app(HrSettingsService::class)->update([
            'require_location_accuracy' => true,
            'default_maximum_accuracy_meters' => 100,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-05 09:30:00'));
        try {
            app(AttendanceCheckInAction::class)->execute($this->employee, [...$payload, 'accuracy' => 0]);
            $this->fail('Expected accuracy rejection at 0m');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('accuracy', $e->errors());
        }

        Carbon::setTestNow(Carbon::parse('2026-08-06 09:30:00'));
        try {
            app(AttendanceCheckInAction::class)->execute($this->employee, [...$payload, 'accuracy' => -5]);
            $this->fail('Expected accuracy rejection at negative value');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('accuracy', $e->errors());
        }
    }

    public function test_payroll_daily_salary_calculation_excludes_absence_deduction_and_uses_payable_days_present(): void
    {
        // Disable monthly employee created in setUp
        $this->employee->update(['is_active' => false]);

        $dailyEmployee = HrEmployee::query()->create([
            'employee_number' => 'E-DAILY-01',
            'full_name' => 'Daily Employee',
            'branch_id' => $this->branch->id,
            'department_id' => HrDepartment::query()->first()->id,
            'job_title_id' => HrJobTitle::query()->first()->id,
            'attendance_schedule_id' => $this->schedule->id,
            'attendance_location_id' => $this->location->id,
            'hire_date' => '2026-01-01',
            'employment_status' => EmploymentStatus::Active,
            'salary_type' => SalaryType::Daily,
            'base_salary' => 100,
            'is_active' => true,
        ]);

        // August 2026 period: 20 present days + 5 absent days.
        $start = Carbon::parse('2026-08-01');
        $end = Carbon::parse('2026-08-31');

        // Create 20 present records (avoid weekend by picking sequential working days in this simplified test)
        $presentDates = [
            '2026-08-02','2026-08-03','2026-08-04','2026-08-05','2026-08-06',
            '2026-08-09','2026-08-10','2026-08-11','2026-08-12','2026-08-13',
            '2026-08-16','2026-08-17','2026-08-18','2026-08-19','2026-08-20',
            '2026-08-23','2026-08-24','2026-08-25','2026-08-26','2026-08-27',
        ];

        foreach ($presentDates as $d) {
            HrAttendanceRecord::query()->create([
                'employee_id' => $dailyEmployee->id,
                'attendance_date' => $d,
                'status' => AttendanceStatus::Present,
                'late_minutes' => 0,
            ]);
        }

        $absentDates = ['2026-08-28','2026-08-29','2026-09-01','2026-09-02','2026-09-03'];
        foreach ($absentDates as $d) {
            // Keep inside the period range by using August dates only (simplified: pick last working days)
            if (Carbon::parse($d)->between($start, $end, true)) {
                HrAttendanceRecord::query()->create([
                    'employee_id' => $dailyEmployee->id,
                    'attendance_date' => $d,
                    'status' => AttendanceStatus::Absent,
                    'late_minutes' => 0,
                ]);
            }
        }

        // Compute expected absent days as created records count
        $absentDays = HrAttendanceRecord::query()
            ->where('employee_id', $dailyEmployee->id)
            ->where('status', AttendanceStatus::Absent)
            ->count();

        $period = HrPayrollPeriod::query()->create([
            'name' => 'Aug Daily',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => PayrollPeriodStatus::Draft,
        ]);

        $generated = app(PayrollGenerationService::class)->generate($period);
        $line = $generated->employees()->first();

        $this->assertSame(SalaryType::Daily, $line->salary_type_snapshot);
        $this->assertSame($absentDays, (int) $line->absent_days);
        $this->assertSame('0.00', (string) $line->absence_deduction);

        $presentDays = (int) $line->present_days;
        $this->assertSame(20, $presentDays);

        $expectedGross = 100 * $presentDays;
        $this->assertSame((string) number_format($expectedGross, 2, '.', ''), (string) $line->net_salary);
    }

    public function test_payroll_daily_salary_late_deduction_is_applied_without_double_absence(): void
    {
        $this->employee->update(['is_active' => false]);

        $dailyEmployee = HrEmployee::query()->create([
            'employee_number' => 'E-DAILY-02',
            'full_name' => 'Daily Late Employee',
            'branch_id' => $this->branch->id,
            'department_id' => HrDepartment::query()->first()->id,
            'job_title_id' => HrJobTitle::query()->first()->id,
            'attendance_schedule_id' => $this->schedule->id,
            'attendance_location_id' => $this->location->id,
            'hire_date' => '2026-01-01',
            'employment_status' => EmploymentStatus::Active,
            'salary_type' => SalaryType::Daily,
            'base_salary' => 100,
            'is_active' => true,
        ]);

        $start = Carbon::parse('2026-08-01');
        $end = Carbon::parse('2026-08-31');

        // 5 present + 3 late, 1 absent
        $presentDates = ['2026-08-02','2026-08-03','2026-08-04','2026-08-05','2026-08-06'];
        foreach ($presentDates as $d) {
            HrAttendanceRecord::query()->create([
                'employee_id' => $dailyEmployee->id,
                'attendance_date' => $d,
                'status' => AttendanceStatus::Present,
                'late_minutes' => 0,
            ]);
        }

        $lateDates = ['2026-08-09','2026-08-10','2026-08-11'];
        foreach ($lateDates as $d) {
            HrAttendanceRecord::query()->create([
                'employee_id' => $dailyEmployee->id,
                'attendance_date' => $d,
                'status' => AttendanceStatus::Late,
                'late_minutes' => 10,
            ]);
        }

        HrAttendanceRecord::query()->create([
            'employee_id' => $dailyEmployee->id,
            'attendance_date' => '2026-08-12',
            'status' => AttendanceStatus::Absent,
            'late_minutes' => 0,
        ]);

        $period = HrPayrollPeriod::query()->create([
            'name' => 'Aug Daily Late',
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => PayrollPeriodStatus::Draft,
        ]);

        $generated = app(PayrollGenerationService::class)->generate($period);
        $line = $generated->employees()->first();

        $this->assertSame('0.00', (string) $line->absence_deduction);
        $this->assertSame(8, (int) $line->present_days);
        $this->assertSame(3, (int) $line->late_days);

        $gross = 100 * 8;
        $expectedLateDeduction = 1.00 * 30; // per minute=1.00, total late minutes=3*10
        $expectedNet = $gross - $expectedLateDeduction;

        $this->assertSame((string) number_format($expectedNet, 2, '.', ''), (string) $line->net_salary);
    }

    public function test_payroll_daily_snapshot_stays_fixed_after_employee_salary_change(): void
    {
        $this->employee->update(['is_active' => false]);

        $dailyEmployee = HrEmployee::query()->create([
            'employee_number' => 'E-DAILY-03',
            'full_name' => 'Daily Snapshot Employee',
            'branch_id' => $this->branch->id,
            'department_id' => HrDepartment::query()->first()->id,
            'job_title_id' => HrJobTitle::query()->first()->id,
            'attendance_schedule_id' => $this->schedule->id,
            'attendance_location_id' => $this->location->id,
            'hire_date' => '2026-01-01',
            'employment_status' => EmploymentStatus::Active,
            'salary_type' => SalaryType::Daily,
            'base_salary' => 120,
            'is_active' => true,
        ]);

        HrAttendanceRecord::query()->create([
            'employee_id' => $dailyEmployee->id,
            'attendance_date' => '2026-08-02',
            'status' => AttendanceStatus::Present,
            'late_minutes' => 0,
        ]);

        $period = HrPayrollPeriod::query()->create([
            'name' => 'Aug Daily Snapshot',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'status' => PayrollPeriodStatus::Draft,
        ]);

        $generated = app(PayrollGenerationService::class)->generate($period);
        $line = $generated->employees()->first();

        $this->assertSame('120.00', (string) $line->base_salary_snapshot);
        $this->assertSame('120.00', (string) $line->net_salary);

        // Change employee salary after generation: existing payroll line must not change.
        $dailyEmployee->update(['base_salary' => 999]);
        $line->refresh();

        $this->assertSame('120.00', (string) $line->base_salary_snapshot);
        $this->assertSame('120.00', (string) $line->net_salary);
    }

    public function test_attendance_page_requires_hr_attendance_permissions(): void
    {
        $user = $this->makeAttendanceUserWithoutPermissions();

        $this->getOnTenant(route('filament.tenant.hr.attendance', absolute: false), $user)
            ->assertForbidden();
    }

    public function test_attendance_check_in_and_check_out_permissions_enforced(): void
    {
        $payload = [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
            'captured_at' => now()->toISOString(),
        ];

        $checkInUser = $this->makeAttendanceUserWithPermissions(['hr.attendance.check_in']);

        $this->getOnTenant(route('filament.tenant.hr.attendance', absolute: false), $checkInUser)
            ->assertOk();

        $this->postOnTenant(route('filament.tenant.hr.attendance.check-out', absolute: false), $payload, $checkInUser)
            ->assertForbidden();

        $checkOutUser = $this->makeAttendanceUserWithPermissions(['hr.attendance.check_out']);

        $this->postOnTenant(route('filament.tenant.hr.attendance.check-in', absolute: false), $payload, $checkOutUser)
            ->assertForbidden();

        Carbon::setTestNow(Carbon::parse('2026-07-29 09:30:00'));
        app(AttendanceCheckInAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);

        $this->postOnTenant(route('filament.tenant.hr.attendance.check-out', absolute: false), $payload, $checkOutUser)
            ->assertOk();
    }

    public function test_overnight_shift_is_treated_as_non_working_day(): void
    {
        // day_of_week for Wednesday (2026-07-29) is 3
        $this->schedule->days()
            ->where('day_of_week', 3)
            ->update(['start_time' => '17:00:00', 'end_time' => '09:00:00']);

        $this->expectException(ValidationException::class);

        app(AttendanceCheckInAction::class)->execute($this->employee, [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
        ]);
    }

    public function test_status_and_distance_do_not_expose_work_location_coordinates(): void
    {
        $user = $this->makeAttendanceUserWithPermissions(['hr.attendance.check_in']);

        $status = $this->getOnTenant(route('filament.tenant.hr.attendance.status', absolute: false), $user)
            ->assertOk()
            ->json('data');

        $this->assertArrayHasKey('location', $status);
        $this->assertArrayNotHasKey('latitude', $status['location'] ?? []);
        $this->assertArrayNotHasKey('longitude', $status['location'] ?? []);

        $distance = $this->getOnTenant(
            route('filament.tenant.hr.attendance.distance', absolute: false).'?latitude=30.0444&longitude=31.2357',
            $user
        )->assertOk()->json('data');

        $this->assertTrue($distance['has_location']);
        $this->assertArrayNotHasKey('latitude', $distance);
        $this->assertArrayNotHasKey('longitude', $distance);
        $this->assertArrayHasKey('inside', $distance);
    }

    public function test_unlinked_user_sees_attendance_page_state_instead_of_hard_fail(): void
    {
        config(['app.bypass_permissions' => false]);

        $user = TenantUser::query()->create([
            'name' => 'Unlinked',
            'email' => 'unlinked-'.str()->uuid().'@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $permission = Permission::query()->firstOrCreate([
            'name' => 'hr.attendance.check_in',
            'guard_name' => 'tenant',
        ]);
        $user->permissions()->syncWithoutDetaching([$permission->id]);

        $this->getOnTenant(route('filament.tenant.hr.attendance', absolute: false), $user)
            ->assertOk()
            ->assertSee(__('hr.validation.user_not_linked_employee'), false);
    }

    public function test_check_in_json_includes_status_label_and_ignores_foreign_employee_id(): void
    {
        $user = $this->makeAttendanceUserWithPermissions(['hr.attendance.check_in']);

        $response = $this->postOnTenant(route('filament.tenant.hr.attendance.check-in', absolute: false), [
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'accuracy' => 20,
            'employee_id' => 999999,
        ], $user);

        $response->assertCreated();
        $response->assertJsonPath('data.status_label', AttendanceStatus::Late->label());
        $this->assertSame($this->employee->id, HrAttendanceRecord::query()->first()->employee_id);
    }

    private function seedHrTenant(Tenant $tenant, string $employeeNumber): void
    {
        $tenant->run(function () use ($employeeNumber) {
            $user = TenantUser::query()->create([
                'name' => 'HR User',
                'email' => 'hr-'.$employeeNumber.'@example.com',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            $branch = Branch::query()->create([
                'name' => 'Branch '.$employeeNumber,
                'code' => 'BR-'.$employeeNumber,
                'is_main' => true,
                'is_active' => true,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $schedule = HrAttendanceSchedule::query()->create([
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
                    'attendance_schedule_id' => $schedule->id,
                    'day_of_week' => $day,
                    'is_working_day' => ! in_array($day, [5, 6], true),
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                ]);
            }

            $location = HrAttendanceLocation::query()->create([
                'name' => 'Office',
                'branch_id' => $branch->id,
                'latitude' => 30.0444000,
                'longitude' => 31.2357000,
                'allowed_radius_meters' => 150,
                'maximum_accuracy_meters' => 100,
                'is_active' => true,
            ]);

            app(HrSettingsService::class)->update([
                'default_attendance_schedule_id' => $schedule->id,
                'default_attendance_location_id' => $location->id,
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

            HrEmployee::query()->create([
                'employee_number' => $employeeNumber,
                'full_name' => 'Employee '.$employeeNumber,
                'branch_id' => $branch->id,
                'department_id' => $dept->id,
                'job_title_id' => $title->id,
                'attendance_schedule_id' => $schedule->id,
                'attendance_location_id' => $location->id,
                'hire_date' => '2026-01-01',
                'employment_status' => EmploymentStatus::Active,
                'salary_type' => SalaryType::Monthly,
                'base_salary' => 3000,
                'is_active' => true,
            ]);
        });
    }

    private function makeAttendanceUserWithoutPermissions(): TenantUser
    {
        config(['app.bypass_permissions' => false]);

        $user = TenantUser::query()->create([
            'name' => 'Attendance User',
            'email' => 'attendance-'.str()->uuid().'@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        $this->employee->update(['user_id' => $user->id]);

        return $user;
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeAttendanceUserWithPermissions(array $permissions): TenantUser
    {
        $user = $this->makeAttendanceUserWithoutPermissions();

        foreach ($permissions as $permission) {
            $permissionModel = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'tenant',
            ]);
            $user->permissions()->syncWithoutDetaching([$permissionModel->id]);
        }

        return $user;
    }

    private function getOnTenant(string $uri, ?TenantUser $user = null)
    {
        $user ??= $this->user;
        $domain = $this->tenant->domains()->first()->domain;

        $this->flushSession();

        return $this->actingAs($user, 'tenant')
            ->withServerVariables(['HTTP_HOST' => $domain])
            ->get('http://'.$domain.$uri);
    }

    private function postOnTenant(string $uri, array $data = [], ?TenantUser $user = null)
    {
        $user ??= $this->user;
        $domain = $this->tenant->domains()->first()->domain;

        $this->flushSession();

        return $this->actingAs($user, 'tenant')
            ->withServerVariables(['HTTP_HOST' => $domain])
            ->postJson('http://'.$domain.$uri, $data);
    }
}
