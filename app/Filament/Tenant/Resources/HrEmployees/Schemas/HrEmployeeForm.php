<?php

namespace App\Filament\Tenant\Resources\HrEmployees\Schemas;

use App\Enums\Hr\AbsenceDeductionType;
use App\Enums\Hr\EmploymentStatus;
use App\Enums\Hr\SalaryType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('employee_number')
                        ->label(__('hr.fields.employee_number'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => __('hr.validation.duplicate_employee_number'),
                        ]),
                    TextInput::make('full_name')
                        ->label(__('hr.fields.full_name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('user_id')
                        ->label(__('hr.fields.user'))
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->nullable()
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => __('hr.validation.user_already_linked'),
                        ]),
                    TextInput::make('email')->label(__('hr.fields.email'))->email()->maxLength(255),
                    TextInput::make('phone')->label(__('hr.fields.phone'))->tel()->maxLength(50),
                    DatePicker::make('hire_date')->label(__('hr.fields.hire_date')),
                    Select::make('branch_id')
                        ->label(__('hr.fields.branch'))
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('department_id')
                        ->label(__('hr.fields.department'))
                        ->relationship('department', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('job_title_id')
                        ->label(__('hr.fields.job_title'))
                        ->relationship('jobTitle', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('attendance_schedule_id')
                        ->label(__('hr.fields.attendance_schedule'))
                        ->relationship('schedule', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('attendance_location_id')
                        ->label(__('hr.fields.attendance_location'))
                        ->relationship('attendanceLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('employment_status')
                        ->label(__('hr.fields.employment_status'))
                        ->options(collect(EmploymentStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                        ->default(EmploymentStatus::Active->value)
                        ->required()
                        ->native(false),
                    Toggle::make('is_active')->label(__('hr.fields.is_active'))->default(true),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.compensation'))
                ->columns(3)
                ->schema([
                    Select::make('salary_type')
                        ->label(__('hr.fields.salary_type'))
                        ->options(collect(SalaryType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                        ->default(SalaryType::Monthly->value)
                        ->required()
                        ->native(false),
                    TextInput::make('base_salary')
                        ->label(__('hr.fields.base_salary'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    TextInput::make('custom_late_grace_minutes')
                        ->label(__('hr.fields.custom_late_grace_minutes'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    Select::make('custom_absence_deduction_type')
                        ->label(__('hr.fields.custom_absence_deduction_type'))
                        ->options(collect(AbsenceDeductionType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                        ->placeholder(__('hr.fields.use_default'))
                        ->native(false)
                        ->nullable(),
                    TextInput::make('custom_absence_deduction_value')
                        ->label(__('hr.fields.custom_absence_deduction_value'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.notes'))
                ->schema([
                    Textarea::make('notes')->label(__('hr.fields.notes'))->rows(3)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
