<?php

namespace App\Filament\Tenant\Resources\SalesReturns;

use App\Filament\Tenant\Resources\SalesReturns\Pages\CreateSalesReturn;
use App\Filament\Tenant\Resources\SalesReturns\Pages\EditSalesReturn;
use App\Filament\Tenant\Resources\SalesReturns\Pages\ListSalesReturns;
use App\Filament\Tenant\Resources\SalesReturns\Pages\ViewSalesReturn;
use App\Filament\Tenant\Resources\SalesReturns\Schemas\SalesReturnForm;
use App\Filament\Tenant\Resources\SalesReturns\Tables\SalesReturnsTable;
use App\Models\Tenant\SalesReturn;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SalesReturnResource extends Resource
{
    protected static ?string $model = SalesReturn::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    protected static ?int $navigationSort = 332;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.sales_returns');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.sales_returns');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.sales_return');
    }

    public static function form(Schema $schema): Schema
    {
        return SalesReturnForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesReturnsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesReturns::route('/'),
            'create' => CreateSalesReturn::route('/create'),
            'view' => ViewSalesReturn::route('/{record}'),
            'edit' => EditSalesReturn::route('/{record}/edit'),
        ];
    }
}
