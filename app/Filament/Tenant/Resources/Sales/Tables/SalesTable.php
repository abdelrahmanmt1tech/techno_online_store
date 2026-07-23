<?php

namespace App\Filament\Tenant\Resources\Sales\Tables;

use App\Enums\Erp\SaleStatus;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('document_number')->label(__('erp.fields.document_number'))->searchable()->sortable(),
                TextColumn::make('customer.name')->label(__('erp.fields.customer')),
                TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof SaleStatus ? $state->label() : (__('erp.sale_statuses.'.$state) ?: $state))
                    ->badge(),
                TextColumn::make('sale_date')->label(__('erp.fields.sale_date'))->date()->sortable(),
                TextColumn::make('grand_total')->label(__('erp.fields.grand_total')),
                TextColumn::make('branch.name')->label(__('erp.fields.branch'))->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('erp.fields.status'))->options(ErpEnumOptions::options(SaleStatus::class))->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
