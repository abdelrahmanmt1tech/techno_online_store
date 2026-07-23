<?php

namespace App\Filament\Tenant\Resources\PurchaseOrders;

use App\Filament\Tenant\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Tenant\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Tenant\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Tenant\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Tenant\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Tenant\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\Tenant\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?int $navigationSort = 320;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.purchases');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.purchase_orders');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.purchase_orders');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.purchase_order');
    }

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'view' => ViewPurchaseOrder::route('/{record}'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
