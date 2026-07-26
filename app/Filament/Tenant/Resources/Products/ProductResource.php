<?php

namespace App\Filament\Tenant\Resources\Products;

use App\Filament\Tenant\Resources\Products\Pages\CreateProduct;
use App\Filament\Tenant\Resources\Products\Pages\EditProduct;
use App\Filament\Tenant\Resources\Products\Pages\ListProducts;
use App\Filament\Tenant\Resources\Products\Pages\ViewProduct;
use App\Filament\Tenant\Resources\Products\RelationManagers\ReviewsRelationManager;
use App\Filament\Tenant\Resources\Products\Schemas\ProductForm;
use App\Filament\Tenant\Resources\Products\Tables\ProductsTable;
use App\Models\Tenant\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cube;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.products');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.products');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.product');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('products.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('products.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('products.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('products.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'view' => ViewProduct::route('/{record}'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ReviewsRelationManager::class,
        ];
    }
}
