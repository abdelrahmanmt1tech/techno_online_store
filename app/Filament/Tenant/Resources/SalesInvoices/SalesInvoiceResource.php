<?php

namespace App\Filament\Tenant\Resources\SalesInvoices;

use App\Filament\Tenant\Resources\SalesInvoices\Pages\CreateSalesInvoice;
use App\Filament\Tenant\Resources\SalesInvoices\Pages\EditSalesInvoice;
use App\Filament\Tenant\Resources\SalesInvoices\Pages\ListSalesInvoices;
use App\Filament\Tenant\Resources\SalesInvoices\Pages\ViewSalesInvoice;
use App\Filament\Tenant\Resources\SalesInvoices\Schemas\SalesInvoiceForm;
use App\Filament\Tenant\Resources\SalesInvoices\Tables\SalesInvoicesTable;
use App\Models\Tenant\SalesInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ReceiptPercent;

    protected static ?int $navigationSort = 331;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.sales_invoices');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.sales_invoices');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.sales_invoice');
    }

    public static function form(Schema $schema): Schema
    {
        return SalesInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesInvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesInvoices::route('/'),
            'create' => CreateSalesInvoice::route('/create'),
            'view' => ViewSalesInvoice::route('/{record}'),
            'edit' => EditSalesInvoice::route('/{record}/edit'),
        ];
    }
}
