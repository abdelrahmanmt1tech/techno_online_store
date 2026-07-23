<?php

namespace App\Filament\Tenant\Resources\Warehouses\Tables;

use App\Enums\Erp\WarehouseType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('erp.fields.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('erp.fields.code'))->searchable(),
                TextColumn::make('branch.name')->label(__('erp.fields.branch'))->sortable(),
                TextColumn::make('warehouse_type')
                    ->label(__('erp.fields.warehouse_type'))
                    ->formatStateUsing(fn ($state) => $state instanceof WarehouseType ? $state->label() : (__('erp.warehouse_types.'.$state) ?: $state)),
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
