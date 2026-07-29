<?php

namespace App\Filament\Tenant\Resources\HrPayrollPeriods\Schemas;

use App\Enums\Hr\PayrollPeriodStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class HrPayrollPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('hr.fields.name'))->required()->maxLength(255),
                    DatePicker::make('start_date')->label(__('hr.fields.start_date'))->required(),
                    DatePicker::make('end_date')->label(__('hr.fields.end_date'))->required(),
                    Textarea::make('notes')->label(__('hr.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('hr.sections.status_info'))
                ->columns(3)
                ->hiddenOn('create')
                ->schema([
                    Select::make('status')
                        ->label(__('hr.fields.status'))
                        ->options(collect(PayrollPeriodStatus::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all())
                        ->disabled()
                        ->dehydrated(false)
                        ->native(false),
                    DateTimePicker::make('generated_at')->label(__('hr.fields.generated_at'))->disabled()->dehydrated(false),
                    DateTimePicker::make('approved_at')->label(__('hr.fields.approved_at'))->disabled()->dehydrated(false),
                    DateTimePicker::make('paid_at')->label(__('hr.fields.paid_at'))->disabled()->dehydrated(false),
                ])
                ->columnSpanFull(),
        ]);
    }
}
