<?php

namespace App\Filament\Tenant\Resources\Warehouses;

use App\Filament\Tenant\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Tenant\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Tenant\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Tenant\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Tenant\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Tenant\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::HomeModern;

    protected static ?int $navigationSort = 301;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.warehouses');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.warehouses');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.warehouse');
    }

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
