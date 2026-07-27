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
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

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
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),
                SelectFilter::make('item_type')
                    ->label(__('erp.fields.item_type'))
                    ->options([
                        'finished_good' => __('erp.item_types.finished_good'),
                        'raw_material' => __('erp.item_types.raw_material'),
                        'consumable' => __('erp.item_types.consumable'),
                        'packaging' => __('erp.item_types.packaging'),
                        'spare_part' => __('erp.item_types.spare_part'),
                        'asset' => __('erp.item_types.asset'),
                        'service' => __('erp.item_types.service'),
                        'other' => __('erp.item_types.other'),
                    ])
                    ->native(false),
                SelectFilter::make('track_stock')
                    ->label(__('erp.fields.track_stock'))
                    ->options([
                        '1' => __('dashboard.yes'),
                        '0' => __('dashboard.no'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()->can('erp.inventory.manage')),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('erp.inventory.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
