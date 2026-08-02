<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Schemas;

use App\Enums\FinancialPeriodStatus;
use App\Models\Tenant\FinancialPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialPeriodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.resources.financial_period.section_main'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('dashboard.resources.financial_period.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->label(__('dashboard.resources.financial_period.code'))
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        DatePicker::make('start_date')
                            ->label(__('dashboard.resources.financial_period.start_date'))
                            ->required(),
                        DatePicker::make('end_date')
                            ->label(__('dashboard.resources.financial_period.end_date'))
                            ->required()
                            ->afterOrEqual('start_date'),
                        Select::make('status')
                            ->label(__('dashboard.resources.financial_period.status'))
                            ->options(FinancialPeriodStatus::options())
                            ->default(FinancialPeriodStatus::DRAFT->value)
                            ->required()
                            ->native(false),
                        Toggle::make('is_current')
                            ->label(__('dashboard.resources.financial_period.is_current'))
                            ->inline(false),
                        Select::make('parent_period_id')
                            ->label(__('dashboard.resources.financial_period.parent_period'))
                            ->options(fn (): array => FinancialPeriod::query()
                                ->orderByDesc('start_date')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Textarea::make('notes')
                            ->label(__('dashboard.resources.financial_period.notes'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
