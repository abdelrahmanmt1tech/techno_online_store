<?php

namespace App\Filament\Tenant\Resources\HrAttendanceRecords\Schemas;

use App\Enums\Hr\AttendanceStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrAttendanceRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr.sections.context'))
                ->columns(2)
                ->schema([
                    Select::make('employee_id')
                        ->label(__('hr.fields.employee'))
                        ->relationship('employee', 'full_name')
                        ->disabled()
                        ->dehydrated(false)
                        ->native(false),
                    DatePicker::make('attendance_date')
                        ->label(__('hr.fields.attendance_date'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.adjustment'))
                ->columns(2)
                ->schema([
                    DateTimePicker::make('check_in_at')->label(__('hr.fields.check_in_at'))->seconds(false),
                    DateTimePicker::make('check_out_at')->label(__('hr.fields.check_out_at'))->seconds(false),
                    Select::make('status')
                        ->label(__('hr.fields.status'))
                        ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                        ->required()
                        ->native(false),
                    TextInput::make('late_minutes')
                        ->label(__('hr.fields.late_minutes'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    TextInput::make('early_leave_minutes')
                        ->label(__('hr.fields.early_leave_minutes'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Textarea::make('admin_note')
                        ->label(__('hr.fields.admin_note'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
