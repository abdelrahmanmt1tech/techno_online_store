<?php

namespace App\Filament\Tenant\Resources\Suppliers;

use App\Filament\Tenant\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Tenant\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Tenant\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Tenant\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Tenant\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Tenant\Supplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Truck;

    protected static ?int $navigationSort = 304;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.suppliers');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.suppliers');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.supplier');
    }

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }
}
