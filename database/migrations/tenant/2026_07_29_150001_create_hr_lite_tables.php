<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_job_titles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_attendance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('late_grace_minutes')->default(15);
            $table->unsignedSmallInteger('early_check_in_minutes')->nullable();
            $table->boolean('allow_check_out_outside_location')->default(false);
            $table->boolean('absence_deduction_enabled')->default(true);
            $table->boolean('late_deduction_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('hr_attendance_schedule_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_schedule_id')->constrained('hr_attendance_schedules')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sunday .. 6=Saturday
            $table->boolean('is_working_day')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();

            $table->unique(['attendance_schedule_id', 'day_of_week'], 'hr_schedule_day_unique');
        });

        Schema::create('hr_attendance_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('allowed_radius_meters')->default(150);
            $table->unsignedInteger('minimum_accuracy_meters')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('default_attendance_schedule_id')->nullable()->constrained('hr_attendance_schedules')->nullOnDelete();
            $table->foreignId('default_attendance_location_id')->nullable()->constrained('hr_attendance_locations')->nullOnDelete();
            $table->unsignedTinyInteger('payroll_day_of_month')->default(1);
            $table->unsignedTinyInteger('working_days_per_month')->default(30);
            $table->string('default_absence_deduction_type', 30)->default('daily_rate');
            $table->decimal('default_absence_fixed_amount', 14, 2)->nullable();
            $table->string('default_late_deduction_type', 30)->default('none');
            $table->decimal('default_late_fixed_amount', 14, 2)->nullable();
            $table->decimal('default_late_amount_per_minute', 14, 4)->nullable();
            $table->decimal('maximum_late_deduction_per_day', 14, 2)->nullable();
            $table->boolean('auto_mark_absent')->default(true);
            $table->time('absence_processing_time')->nullable();
            $table->boolean('require_location_accuracy')->default(false);
            $table->unsignedInteger('default_maximum_accuracy_meters')->nullable();
            $table->timestamps();
        });

        Schema::create('hr_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('hr_departments')->nullOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained('hr_job_titles')->nullOnDelete();
            $table->foreignId('attendance_schedule_id')->nullable()->constrained('hr_attendance_schedules')->nullOnDelete();
            $table->foreignId('attendance_location_id')->nullable()->constrained('hr_attendance_locations')->nullOnDelete();
            $table->date('hire_date')->nullable();
            $table->string('employment_status', 20)->default('active');
            $table->string('salary_type', 20)->default('monthly');
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->unsignedSmallInteger('custom_late_grace_minutes')->nullable();
            $table->string('custom_absence_deduction_type', 30)->nullable();
            $table->decimal('custom_absence_deduction_value', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('employee_number');
            $table->unique('user_id');
            $table->index(['employment_status', 'is_active']);
        });

        Schema::create('hr_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->foreignId('schedule_id')->nullable()->constrained('hr_attendance_schedules')->nullOnDelete();
            $table->foreignId('attendance_location_id')->nullable()->constrained('hr_attendance_locations')->nullOnDelete();
            $table->dateTime('scheduled_start_at')->nullable();
            $table->dateTime('scheduled_end_at')->nullable();
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_in_accuracy', 10, 2)->nullable();
            $table->decimal('check_in_distance_meters', 12, 2)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->decimal('check_out_accuracy', 10, 2)->nullable();
            $table->decimal('check_out_distance_meters', 12, 2)->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('worked_minutes')->nullable();
            $table->string('status', 20)->default('incomplete');
            $table->string('source', 20)->default('employee');
            $table->text('admin_note')->nullable();
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('adjusted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date'], 'hr_attendance_employee_date_unique');
            $table->index(['attendance_date', 'status']);
        });

        Schema::create('hr_payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        Schema::create('hr_payroll_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('hr_payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->decimal('base_salary_snapshot', 14, 2);
            $table->string('salary_type_snapshot', 20);
            $table->unsignedSmallInteger('working_days_count')->default(0);
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('late_days')->default(0);
            $table->unsignedSmallInteger('absent_days')->default(0);
            $table->unsignedInteger('total_late_minutes')->default(0);
            $table->decimal('absence_deduction', 14, 2)->default(0);
            $table->decimal('late_deduction', 14, 2)->default(0);
            $table->decimal('manual_deduction', 14, 2)->default(0);
            $table->string('manual_deduction_reason')->nullable();
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('net_salary', 14, 2)->default(0);
            $table->json('calculation_details')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id'], 'hr_payroll_period_employee_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_payroll_employees');
        Schema::dropIfExists('hr_payroll_periods');
        Schema::dropIfExists('hr_attendance_records');
        Schema::dropIfExists('hr_employees');
        Schema::dropIfExists('hr_settings');
        Schema::dropIfExists('hr_attendance_locations');
        Schema::dropIfExists('hr_attendance_schedule_days');
        Schema::dropIfExists('hr_attendance_schedules');
        Schema::dropIfExists('hr_job_titles');
        Schema::dropIfExists('hr_departments');
    }
};
