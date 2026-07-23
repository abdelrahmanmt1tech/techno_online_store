<?php

namespace App\Filament\Tenant\Resources\StockBalances\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('quantity_on_hand', 'desc')
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label(__('erp.fields.warehouse'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventoryItem.name')
                    ->label(__('erp.fields.inventory_item'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('inventoryItem.sku')
                    ->label(__('erp.fields.sku'))
                    ->toggleable(),
                TextColumn::make('quantity_on_hand')
                    ->label(__('erp.fields.quantity_on_hand'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label(__('erp.fields.warehouse'))
                    ->relationship('warehouse', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
                SelectFilter::make('inventory_item_id')
                    ->label(__('erp.fields.inventory_item'))
                    ->relationship('inventoryItem', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
