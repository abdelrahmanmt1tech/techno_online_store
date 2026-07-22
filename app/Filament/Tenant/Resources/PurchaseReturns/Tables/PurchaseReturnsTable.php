<?php

namespace App\Filament\Tenant\Resources\PurchaseReturns\Tables;

use App\Enums\Erp\DocumentStatus;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('document_number')->label(__('erp.fields.document_number'))->searchable()->sortable(),
                TextColumn::make('supplier.name')->label(__('erp.fields.supplier')),
                TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof DocumentStatus ? $state->label() : (__('erp.document_statuses.'.$state) ?: $state))
                    ->badge(),
                TextColumn::make('return_date')->label(__('erp.fields.return_date'))->date()->sortable(),
                TextColumn::make('warehouse.name')->label(__('erp.fields.warehouse'))->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('erp.fields.status'))->options(ErpEnumOptions::options(DocumentStatus::class))->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
