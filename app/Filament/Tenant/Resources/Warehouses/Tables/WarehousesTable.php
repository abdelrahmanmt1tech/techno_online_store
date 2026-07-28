<?php

namespace App\Filament\Tenant\Resources\Warehouses\Tables;

use App\Enums\Erp\WarehouseType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),
                SelectFilter::make('warehouse_type')
                    ->label(__('erp.fields.warehouse_type'))
                    ->options([
                        'regular' => __('erp.warehouse_types.regular'),
                        'central' => __('erp.warehouse_types.central'),
                        'returns' => __('erp.warehouse_types.returns'),
                        'damaged' => __('erp.warehouse_types.damaged'),
                        'other' => __('erp.warehouse_types.other'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()->can('erp.warehouses.manage')),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('erp.warehouses.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
