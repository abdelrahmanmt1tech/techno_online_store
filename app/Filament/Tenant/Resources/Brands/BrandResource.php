<?php

namespace App\Filament\Tenant\Resources\Brands;

use App\Filament\Tenant\Resources\Brands\Pages\CreateBrand;
use App\Filament\Tenant\Resources\Brands\Pages\EditBrand;
use App\Filament\Tenant\Resources\Brands\Pages\ListBrands;
use App\Filament\Tenant\Resources\Brands\Schemas\BrandForm;
use App\Filament\Tenant\Resources\Brands\Tables\BrandsTable;
use App\Models\Tenant\Brand;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?int $navigationSort = 280;

    public static function getNavigationGroup(): ?string
    {
        return __('commerce.nav.commerce');
    }

    public static function getNavigationLabel(): string
    {
        return __('commerce.resources.brands');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commerce.resources.brands');
    }

    public static function getModelLabel(): string
    {
        return __('commerce.resources.brand');
    }

    public static function form(Schema $schema): Schema
    {
        return BrandForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BrandsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBrands::route('/'),
            'create' => CreateBrand::route('/create'),
            'edit' => EditBrand::route('/{record}/edit'),
        ];
    }
}
