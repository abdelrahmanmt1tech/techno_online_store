<?php

namespace App\Filament\Tenant\Resources\PurchaseInvoices;

use App\Filament\Tenant\Resources\PurchaseInvoices\Pages\CreatePurchaseInvoice;
use App\Filament\Tenant\Resources\PurchaseInvoices\Pages\EditPurchaseInvoice;
use App\Filament\Tenant\Resources\PurchaseInvoices\Pages\ListPurchaseInvoices;
use App\Filament\Tenant\Resources\PurchaseInvoices\Pages\ViewPurchaseInvoice;
use App\Filament\Tenant\Resources\PurchaseInvoices\Schemas\PurchaseInvoiceForm;
use App\Filament\Tenant\Resources\PurchaseInvoices\Tables\PurchaseInvoicesTable;
use App\Models\Tenant\PurchaseInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceResource extends Resource
{
    protected static ?string $model = PurchaseInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 322;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.purchases');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.purchase_invoices');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.purchase_invoices');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.purchase_invoice');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_enabled(\App\Support\Modules\TenantModule::Pos) && (Auth::user()->can('erp.purchase_invoices.view'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('erp.purchase_invoices.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('erp.purchase_invoices.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('erp.purchase_invoices.manage');
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseInvoicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseInvoices::route('/'),
            'create' => CreatePurchaseInvoice::route('/create'),
            'view' => ViewPurchaseInvoice::route('/{record}'),
            'edit' => EditPurchaseInvoice::route('/{record}/edit'),
        ];
    }
}
