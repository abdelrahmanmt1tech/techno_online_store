<?php

namespace App\Filament\Tenant\Resources\PosRegisters\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PosRegistersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('branch.name')->label(__('erp.fields.branch'))->searchable()->sortable(),
                TextColumn::make('warehouse.name')->label(__('erp.fields.warehouse_id'))->toggleable(),
                TextColumn::make('cashDrawer.name')->label(__('commerce.fields.cash_drawer'))->toggleable(),
                TextColumn::make('name')->label(__('erp.fields.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('erp.fields.code'))->searchable(),
                TextColumn::make('receipt_prefix')->label(__('commerce.fields.receipt_prefix'))->toggleable(),
                ToggleColumn::make('is_active')->label(__('erp.fields.is_active')),
            ])
            ->filters([
                SelectFilter::make('branch_id')
                    ->label(__('erp.fields.branch'))
                    ->relationship('branch', 'name')
                    ->native(false),
                SelectFilter::make('is_active')
                    ->label(__('erp.fields.is_active'))
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
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
