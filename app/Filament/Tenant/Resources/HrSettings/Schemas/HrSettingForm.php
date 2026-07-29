<?php

namespace App\Filament\Tenant\Resources\HrSettings\Schemas;

use App\Enums\Hr\AbsenceDeductionType;
use App\Enums\Hr\LateDeductionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr.sections.payroll_defaults'))
                ->columns(3)
                ->schema([
                    Select::make('default_attendance_schedule_id')
                        ->label(__('hr.fields.default_attendance_schedule'))
                        ->relationship('defaultSchedule', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('default_attendance_location_id')
                        ->label(__('hr.fields.default_attendance_location'))
                        ->relationship('defaultLocation', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    TextInput::make('payroll_day_of_month')
                        ->label(__('hr.fields.payroll_day_of_month'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(31)
                        ->default(1)
                        ->required(),
                    TextInput::make('working_days_per_month')
                        ->label(__('hr.fields.working_days_per_month'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(31)
                        ->default(30)
                        ->required(),
                    Toggle::make('auto_mark_absent')
                        ->label(__('hr.fields.auto_mark_absent'))
                        ->default(true),
                    TimePicker::make('absence_processing_time')
                        ->label(__('hr.fields.absence_processing_time'))
                        ->seconds(false),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.absence_deduction'))
                ->columns(2)
                ->schema([
                    Select::make('default_absence_deduction_type')
                        ->label(__('hr.fields.default_absence_deduction_type'))
                        ->options(collect(AbsenceDeductionType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                        ->default(AbsenceDeductionType::DailyRate->value)
                        ->required()
                        ->native(false),
                    TextInput::make('default_absence_fixed_amount')
                        ->label(__('hr.fields.default_absence_fixed_amount'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.late_deduction'))
                ->columns(3)
                ->schema([
                    Select::make('default_late_deduction_type')
                        ->label(__('hr.fields.default_late_deduction_type'))
                        ->options(collect(LateDeductionType::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                        ->default(LateDeductionType::None->value)
                        ->required()
                        ->native(false),
                    TextInput::make('default_late_fixed_amount')
                        ->label(__('hr.fields.default_late_fixed_amount'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    TextInput::make('default_late_amount_per_minute')
                        ->label(__('hr.fields.default_late_amount_per_minute'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    TextInput::make('maximum_late_deduction_per_day')
                        ->label(__('hr.fields.maximum_late_deduction_per_day'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.location_rules'))
                ->columns(2)
                ->schema([
                    Toggle::make('require_location_accuracy')
                        ->label(__('hr.fields.require_location_accuracy'))
                        ->default(false),
                    TextInput::make('default_maximum_accuracy_meters')
                        ->label(__('hr.fields.default_maximum_accuracy_meters'))
                        ->numeric()
                        ->minValue(1)
                        ->nullable(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
