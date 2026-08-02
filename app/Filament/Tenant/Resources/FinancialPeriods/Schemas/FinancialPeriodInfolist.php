<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Schemas;

use App\Models\Tenant\FinancialPeriod;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialPeriodInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.resources.financial_period.section_main'))
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('name')
                                ->label(__('dashboard.resources.financial_period.name')),
                            TextEntry::make('code')
                                ->label(__('dashboard.resources.financial_period.code'))
                                ->copyable(),
                            TextEntry::make('status')
                                ->label(__('dashboard.resources.financial_period.status'))
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state),
                            IconEntry::make('is_current')
                                ->label(__('dashboard.resources.financial_period.is_current'))
                                ->boolean(),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('start_date')
                                ->label(__('dashboard.resources.financial_period.start_date'))
                                ->date(),
                            TextEntry::make('end_date')
                                ->label(__('dashboard.resources.financial_period.end_date'))
                                ->date(),
                            TextEntry::make('closed_at')
                                ->label(__('dashboard.resources.financial_period.closed_at'))
                                ->dateTime()
                                ->placeholder('-'),
                            TextEntry::make('reopened_at')
                                ->label(__('dashboard.resources.financial_period.reopened_at'))
                                ->dateTime()
                                ->placeholder('-'),
                        ]),
                        TextEntry::make('parentPeriod.name')
                            ->label(__('dashboard.resources.financial_period.parent_period'))
                            ->placeholder('-'),
                        TextEntry::make('notes')
                            ->label(__('dashboard.resources.financial_period.notes'))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make(__('dashboard.resources.financial_period.section_summary'))
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('operations_count')
                                ->label(__('dashboard.resources.financial_period.operations_count'))
                                ->state(fn (FinancialPeriod $record) => $record->operations()->count())
                                ->badge(),
                            TextEntry::make('balances_count')
                                ->label(__('dashboard.resources.financial_period.balances_count'))
                                ->state(fn (FinancialPeriod $record) => $record->balances()->count())
                                ->badge(),
                            TextEntry::make('closings_count')
                                ->label(__('dashboard.resources.financial_period.closings_count'))
                                ->state(fn (FinancialPeriod $record) => $record->closings()->count())
                                ->badge(),
                            TextEntry::make('transfers_count')
                                ->label(__('dashboard.resources.financial_period.transfers_count'))
                                ->state(fn (FinancialPeriod $record) => $record->transfersFrom()->count())
                                ->badge(),
                        ]),
                    ]),
            ]);
    }
}
