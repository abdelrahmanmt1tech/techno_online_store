<?php

namespace App\Filament\Tenant\Resources\InvoicePayments\Tables;

use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\PaymentMethod;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoicePaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('paid_at', 'desc')
            ->columns([
                TextColumn::make('document_number')->label(__('erp.fields.document_number'))->searchable()->sortable(),
                TextColumn::make('payable_type')
                    ->label(__('erp.fields.payable_type'))
                    ->formatStateUsing(fn ($state) => $state instanceof InvoicePayableType
                        ? __('erp.invoice_payable_types.'.$state->value)
                        : (__('erp.invoice_payable_types.'.$state) ?: $state)),
                TextColumn::make('payable_id')->label(__('erp.fields.payable_id')),
                TextColumn::make('payment_method')
                    ->label(__('erp.fields.payment_method'))
                    ->formatStateUsing(fn ($state) => $state instanceof PaymentMethod ? $state->label() : (__('erp.payment_methods.'.$state) ?: $state)),
                TextColumn::make('amount')->label(__('erp.fields.amount'))->sortable(),
                TextColumn::make('payment_reference')->label(__('erp.fields.payment_reference'))->toggleable(),
                TextColumn::make('paid_at')->label(__('erp.fields.paid_at'))->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('status')->label(__('erp.fields.status'))->badge(),
            ])
            ->filters([
                SelectFilter::make('payable_type')
                    ->label(__('erp.fields.payable_type'))
                    ->options([
                        InvoicePayableType::SalesInvoice->value => __('erp.invoice_payable_types.sales_invoice'),
                        InvoicePayableType::PurchaseInvoice->value => __('erp.invoice_payable_types.purchase_invoice'),
                    ])
                    ->native(false),
                SelectFilter::make('payment_method')
                    ->label(__('erp.fields.payment_method'))
                    ->options(ErpEnumOptions::options(PaymentMethod::class))
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([ViewAction::make()])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
