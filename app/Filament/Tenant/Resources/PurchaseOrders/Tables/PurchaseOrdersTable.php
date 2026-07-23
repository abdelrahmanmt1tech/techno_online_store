<?php

namespace App\Filament\Tenant\Resources\PurchaseOrders\Tables;

use App\Enums\Erp\PurchaseOrderStatus;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('document_number')->label(__('erp.fields.document_number'))->searchable()->sortable(),
                TextColumn::make('supplier.name')->label(__('erp.fields.supplier'))->searchable(),
                TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof PurchaseOrderStatus ? $state->label() : (__('erp.purchase_order_statuses.'.$state) ?: $state))
                    ->badge(),
                TextColumn::make('order_date')->label(__('erp.fields.order_date'))->date()->sortable(),
                TextColumn::make('grand_total')->label(__('erp.fields.grand_total'))->sortable(),
                TextColumn::make('branch.name')->label(__('erp.fields.branch'))->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('erp.fields.status'))
                    ->options(ErpEnumOptions::options(PurchaseOrderStatus::class))
                    ->native(false),
                SelectFilter::make('supplier_id')
                    ->label(__('erp.fields.supplier'))
                    ->relationship('supplier', 'name')
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
