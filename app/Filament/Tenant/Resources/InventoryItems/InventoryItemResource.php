<?php

namespace App\Filament\Tenant\Resources\InventoryItems;

use App\Filament\Tenant\Resources\InventoryItems\Pages\CreateInventoryItem;
use App\Filament\Tenant\Resources\InventoryItems\Pages\EditInventoryItem;
use App\Filament\Tenant\Resources\InventoryItems\Pages\ListInventoryItems;
use App\Filament\Tenant\Resources\InventoryItems\Schemas\InventoryItemForm;
use App\Filament\Tenant\Resources\InventoryItems\Tables\InventoryItemsTable;
use App\Models\Tenant\InventoryItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryItemResource extends Resource
{
    protected static ?string $model = InventoryItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cube;

    protected static ?int $navigationSort = 303;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.inventory_items');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.inventory_items');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.inventory_item');
    }

    public static function form(Schema $schema): Schema
    {
        return InventoryItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryItems::route('/'),
            'create' => CreateInventoryItem::route('/create'),
            'edit' => EditInventoryItem::route('/{record}/edit'),
        ];
    }
}
