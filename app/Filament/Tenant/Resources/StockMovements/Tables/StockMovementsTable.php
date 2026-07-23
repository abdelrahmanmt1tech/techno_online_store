<?php

namespace App\Filament\Tenant\Resources\StockMovements\Tables;

use App\Enums\Erp\MovementDirection;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('movement_date', 'desc')
            ->columns([
                TextColumn::make('stockTransaction.document_number')
                    ->label(__('erp.fields.document_number'))
                    ->searchable(),
                TextColumn::make('inventoryItem.name')
                    ->label(__('erp.fields.inventory_item'))
                    ->searchable(),
                TextColumn::make('warehouse.name')
                    ->label(__('erp.fields.warehouse')),
                TextColumn::make('direction')
                    ->label(__('erp.fields.direction'))
                    ->formatStateUsing(fn ($state) => $state instanceof MovementDirection ? $state->label() : (__('erp.movement_directions.'.$state) ?: $state))
                    ->badge(),
                TextColumn::make('quantity')->label(__('erp.fields.quantity')),
                TextColumn::make('unit_cost')->label(__('erp.fields.unit_cost')),
                TextColumn::make('total_cost')->label(__('erp.fields.total_cost')),
                TextColumn::make('movement_date')->label(__('erp.fields.movement_date'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label(__('erp.fields.warehouse'))
                    ->relationship('warehouse', 'name')
                    ->native(false),
                SelectFilter::make('direction')
                    ->label(__('erp.fields.direction'))
                    ->options(ErpEnumOptions::options(MovementDirection::class))
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
