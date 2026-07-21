<?php

namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('code')
                    ->label(__('dashboard.currency_code'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('symbol')
                    ->label(__('dashboard.currency_symbol')),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

                TextColumn::make('sort_order')
                    ->label(__('dashboard.sort_order'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('dashboard.active'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('dashboard.no_currencies'))
            ->emptyStateDescription(__('dashboard.no_currencies_desc'))
            ->emptyStateActions([]);
    }
}
