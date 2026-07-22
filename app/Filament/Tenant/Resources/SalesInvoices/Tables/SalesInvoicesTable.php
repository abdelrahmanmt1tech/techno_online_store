<?php

namespace App\Filament\Tenant\Resources\SalesInvoices\Tables;

use App\Enums\Erp\InvoiceStatus;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('document_number')->label(__('erp.fields.document_number'))->searchable()->sortable(),
                TextColumn::make('customer.name')->label(__('erp.fields.customer')),
                TextColumn::make('sale.document_number')->label(__('erp.fields.sale'))->toggleable(),
                TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof InvoiceStatus ? $state->label() : (__('erp.invoice_statuses.'.$state) ?: $state))
                    ->badge(),
                TextColumn::make('invoice_date')->label(__('erp.fields.invoice_date'))->date()->sortable(),
                TextColumn::make('grand_total')->label(__('erp.fields.grand_total')),
                TextColumn::make('paid_amount')->label(__('erp.fields.paid_amount')),
                TextColumn::make('due_amount')->label(__('erp.fields.due_amount')),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('erp.fields.status'))->options(ErpEnumOptions::options(InvoiceStatus::class))->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
