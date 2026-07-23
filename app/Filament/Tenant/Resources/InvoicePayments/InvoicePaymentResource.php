<?php

namespace App\Filament\Tenant\Resources\InvoicePayments;

use App\Filament\Tenant\Resources\InvoicePayments\Pages\ListInvoicePayments;
use App\Filament\Tenant\Resources\InvoicePayments\Pages\ViewInvoicePayment;
use App\Filament\Tenant\Resources\InvoicePayments\Tables\InvoicePaymentsTable;
use App\Models\Tenant\InvoicePayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InvoicePaymentResource extends Resource
{
    protected static ?string $model = InvoicePayment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?int $navigationSort = 333;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.invoice_payments');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.invoice_payments');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.invoice_payment');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return InvoicePaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoicePayments::route('/'),
            'view' => ViewInvoicePayment::route('/{record}'),
        ];
    }
}
