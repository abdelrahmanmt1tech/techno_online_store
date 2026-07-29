<?php

namespace App\Filament\Tenant\Resources\HrAttendanceSchedules\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrAttendanceScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('hr.fields.name'))->required()->maxLength(255),
                    Toggle::make('is_default')->label(__('hr.fields.is_default')),
                    Toggle::make('is_active')->label(__('hr.fields.is_active'))->default(true),
                    TextInput::make('late_grace_minutes')
                        ->label(__('hr.fields.late_grace_minutes'))
                        ->numeric()
                        ->minValue(0)
                        ->default(15)
                        ->required(),
                    TextInput::make('early_check_in_minutes')
                        ->label(__('hr.fields.early_check_in_minutes'))
                        ->numeric()
                        ->minValue(0)
                        ->nullable(),
                    Toggle::make('allow_check_out_outside_location')
                        ->label(__('hr.fields.allow_check_out_outside_location')),
                    Toggle::make('absence_deduction_enabled')
                        ->label(__('hr.fields.absence_deduction_enabled'))
                        ->default(true),
                    Toggle::make('late_deduction_enabled')
                        ->label(__('hr.fields.late_deduction_enabled'))
                        ->default(true),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.schedule_days'))
                ->schema([
                    Repeater::make('days')
                        ->label('')
                        ->relationship()
                        ->schema([
                            Select::make('day_of_week')
                                ->label(__('hr.fields.day_of_week'))
                                ->options([
                                    0 => __('hr.day_of_week.0'),
                                    1 => __('hr.day_of_week.1'),
                                    2 => __('hr.day_of_week.2'),
                                    3 => __('hr.day_of_week.3'),
                                    4 => __('hr.day_of_week.4'),
                                    5 => __('hr.day_of_week.5'),
                                    6 => __('hr.day_of_week.6'),
                                ])
                                ->required()
                                ->native(false),
                            Toggle::make('is_working_day')
                                ->label(__('hr.fields.is_working_day'))
                                ->default(true)
                                ->live(),
                            TimePicker::make('start_time')
                                ->label(__('hr.fields.start_time'))
                                ->seconds(false)
                                ->visible(fn ($get) => (bool) $get('is_working_day')),
                            TimePicker::make('end_time')
                                ->label(__('hr.fields.end_time'))
                                ->seconds(false)
                                ->visible(fn ($get) => (bool) $get('is_working_day')),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->minItems(0)
                        ->maxItems(7)
                        ->addActionLabel(__('hr.actions.add_day'))
                        ->reorderable(false)
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
