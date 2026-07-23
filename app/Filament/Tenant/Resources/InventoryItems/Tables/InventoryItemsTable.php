<?php

namespace App\Filament\Tenant\Resources\InventoryItems\Tables;

use App\Enums\Erp\InventoryItemType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class InventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('erp.fields.name'))->searchable()->sortable(),
                TextColumn::make('sku')->label(__('erp.fields.sku'))->searchable(),
                TextColumn::make('item_type')
                    ->label(__('erp.fields.item_type'))
                    ->formatStateUsing(fn ($state) => $state instanceof InventoryItemType ? $state->label() : (__('erp.item_types.'.$state) ?: $state)),
                TextColumn::make('unit.name')->label(__('erp.fields.unit')),
                IconColumn::make('track_stock')->label(__('erp.fields.track_stock'))->boolean(),
                ToggleColumn::make('is_active')->label(__('erp.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
