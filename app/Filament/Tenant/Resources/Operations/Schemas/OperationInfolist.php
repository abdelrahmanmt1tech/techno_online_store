<?php

namespace App\Filament\Tenant\Resources\Operations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OperationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.resources.operation.section_main'))
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('id')
                                ->label(__('dashboard.resources.operation.operation_no')),
                            TextEntry::make('financialPeriod.name')
                                ->label(__('dashboard.resources.operation.financial_period'))
                                ->placeholder('-'),
                            TextEntry::make('operation_type')
                                ->label(__('dashboard.resources.operation.operation_type'))
                                ->badge()
                                ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state),
                            TextEntry::make('date')
                                ->label(__('dashboard.resources.operation.date'))
                                ->date(),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('total_debit')
                                ->label(__('dashboard.resources.operation.total_debit'))
                                ->badge()
                                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                            TextEntry::make('total_credit')
                                ->label(__('dashboard.resources.operation.total_credit'))
                                ->badge()
                                ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                            TextEntry::make('posted_at')
                                ->label(__('dashboard.resources.operation.posted_at'))
                                ->dateTime()
                                ->placeholder('-'),
                            TextEntry::make('source_operation_id')
                                ->label(__('dashboard.resources.operation.source_operation'))
                                ->placeholder('-'),
                        ]),
                        Grid::make(4)->schema([
                            IconEntry::make('is_posted')
                                ->label(__('dashboard.resources.operation.is_posted'))
                                ->boolean(),
                            IconEntry::make('is_locked')
                                ->label(__('dashboard.resources.operation.is_locked'))
                                ->boolean(),
                            IconEntry::make('is_system_generated')
                                ->label(__('dashboard.resources.operation.is_system_generated'))
                                ->boolean(),
                            IconEntry::make('settlement')
                                ->label(__('dashboard.resources.operation.account_type'))
                                ->boolean(),
                        ]),
                        TextEntry::make('comment')
                            ->label(__('dashboard.resources.operation.comment_label'))
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
                Section::make(__('dashboard.resources.operation.section_entries'))
                    ->schema([
                        RepeatableEntry::make('entries')
                            ->label(__('dashboard.resources.operation.entries'))
                            ->schema([
                                TextEntry::make('accountTree.account_name')
                                    ->label(__('dashboard.resources.operation.account')),
                                TextEntry::make('entry_type')
                                    ->label(__('dashboard.resources.operation.entry_type'))
                                    ->placeholder('-'),
                                TextEntry::make('branch.name')
                                    ->label(__('dashboard.resources.operation.branch'))
                                    ->placeholder('-'),
                                TextEntry::make('day_date')
                                    ->label(__('dashboard.resources.operation.date'))
                                    ->date(),
                                TextEntry::make('debit')
                                    ->label(__('dashboard.resources.operation.debit'))
                                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                                TextEntry::make('credit')
                                    ->label(__('dashboard.resources.operation.credit'))
                                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                                TextEntry::make('notes')
                                    ->label(__('dashboard.resources.operation.notes'))
                                    ->columnSpanFull(),
                            ])
                            ->columns(6)
                            ->contained(false),
                    ])
                    ->columns(1),
                Section::make(__('dashboard.resources.operation.accounts_centers_section'))
                    ->schema([
                        TextEntry::make('manual_accounts_centers_debit_total')
                            ->label(__('dashboard.resources.operation.total_debit'))
                            ->state(fn ($record): float => (float) $record->accountsCenterMovements()
                                ->where('movement_type', 'manual_operation')
                                ->where('amount', '>', 0)
                                ->sum('amount'))
                            ->badge()
                            ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                        TextEntry::make('manual_accounts_centers_credit_total')
                            ->label(__('dashboard.resources.operation.total_credit'))
                            ->state(fn ($record): float => (float) abs((float) $record->accountsCenterMovements()
                                ->where('movement_type', 'manual_operation')
                                ->where('amount', '<', 0)
                                ->sum('amount')))
                            ->badge()
                            ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                        RepeatableEntry::make('accounts_center_entries_preview')
                            ->label(__('dashboard.resources.operation.accounts_centers_entries'))
                            ->state(fn ($record): array => $record->accountsCenterMovements()
                                ->where('movement_type', 'manual_operation')
                                ->orderBy('id')
                                ->get()
                                ->map(fn ($movement): array => [
                                    'accounts_center_name' => $movement->accountsCenter?->name,
                                    'movement_date' => $movement->movement_date,
                                    'debit' => (float) $movement->amount > 0 ? (float) $movement->amount : 0.0,
                                    'credit' => (float) $movement->amount < 0 ? abs((float) $movement->amount) : 0.0,
                                    'notes' => $movement->notes,
                                ])
                                ->all())
                            ->schema([
                                TextEntry::make('accounts_center_name')
                                    ->label(__('dashboard.resources.operation.accounts_center'))
                                    ->placeholder('-'),
                                TextEntry::make('movement_date')
                                    ->label(__('dashboard.resources.operation.date'))
                                    ->date()
                                    ->placeholder('-'),
                                TextEntry::make('debit')
                                    ->label(__('dashboard.resources.operation.debit'))
                                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                                TextEntry::make('credit')
                                    ->label(__('dashboard.resources.operation.credit'))
                                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                                TextEntry::make('notes')
                                    ->label(__('dashboard.resources.operation.notes'))
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ])
                            ->columns(4)
                            ->contained(false),
                    ])
                    ->columns(2),
            ]);
    }
}
