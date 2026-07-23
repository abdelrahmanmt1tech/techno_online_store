<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Tables;

use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\StockTransactionType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransactionsTable
{
    public static function configure(Table $table, bool $showTypeFilter = true): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('document_number')
                    ->label(__('erp.fields.document_number'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaction_type')
                    ->label(__('erp.fields.transaction_type'))
                    ->formatStateUsing(fn ($state) => $state instanceof StockTransactionType ? $state->label() : (__('erp.stock_transaction_types.'.$state) ?: $state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof DocumentStatus ? $state->label() : (__('erp.document_statuses.'.$state) ?: $state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label(__('erp.fields.branch'))
                    ->toggleable(),
                TextColumn::make('sourceWarehouse.name')
                    ->label(__('erp.fields.source_warehouse'))
                    ->toggleable(),
                TextColumn::make('destinationWarehouse.name')
                    ->label(__('erp.fields.destination_warehouse'))
                    ->toggleable(),
                TextColumn::make('transaction_date')
                    ->label(__('erp.fields.transaction_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('posted_at')
                    ->label(__('erp.fields.posted_at'))
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters(array_filter([
                SelectFilter::make('status')
                    ->label(__('erp.fields.status'))
                    ->options(ErpEnumOptions::options(DocumentStatus::class))
                    ->native(false),
                $showTypeFilter
                    ? SelectFilter::make('transaction_type')
                        ->label(__('erp.fields.transaction_type'))
                        ->options(ErpEnumOptions::options(StockTransactionType::class))
                        ->native(false)
                    : null,
            ]), layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->emptyStateHeading(__('erp.empty.default'))
            ->emptyStateDescription(__('erp.empty.default_desc'));
    }
}
