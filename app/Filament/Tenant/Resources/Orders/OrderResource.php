<?php

namespace App\Filament\Tenant\Resources\Orders;

use App\Filament\Tenant\Resources\Orders\Pages\CreateOrder;
use App\Filament\Tenant\Resources\Orders\Pages\EditOrder;
use App\Filament\Tenant\Resources\Orders\Pages\ListOrders;
use App\Filament\Tenant\Resources\Orders\Pages\ViewOrder;
use App\Filament\Tenant\Resources\Orders\Schemas\OrderForm;
use App\Filament\Tenant\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Tenant\Resources\Orders\Tables\OrdersTable;
use App\Models\Tenant\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShoppingBag;

    protected static ?int $navigationSort = 51;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.store_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.orders');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.orders');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.order');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
