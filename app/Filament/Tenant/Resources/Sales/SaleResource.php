<?php

namespace App\Filament\Tenant\Resources\Sales;

use App\Filament\Tenant\Resources\Sales\Pages\CreateSale;
use App\Filament\Tenant\Resources\Sales\Pages\EditSale;
use App\Filament\Tenant\Resources\Sales\Pages\ListSales;
use App\Filament\Tenant\Resources\Sales\Pages\ViewSale;
use App\Filament\Tenant\Resources\Sales\Schemas\SaleForm;
use App\Filament\Tenant\Resources\Sales\Tables\SalesTable;
use App\Models\Tenant\Sale;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingCart;

    protected static ?int $navigationSort = 330;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.sales');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.sales');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.sale');
    }

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'view' => ViewSale::route('/{record}'),
            'edit' => EditSale::route('/{record}/edit'),
        ];
    }
}
