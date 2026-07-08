<?php

namespace App\Filament\Tenant\Resources\Categories;

use App\Filament\Tenant\Resources\Categories\Pages\CreateCategory;
use App\Filament\Tenant\Resources\Categories\Pages\EditCategory;
use App\Filament\Tenant\Resources\Categories\Pages\ViewCategory;
use App\Filament\Tenant\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Tenant\Resources\Categories\Tables\CategoriesTable;
use App\Models\Tenant\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Openplain\FilamentTreeView\Fields\IconField;
use Openplain\FilamentTreeView\Fields\TextField;
use Openplain\FilamentTreeView\Tree;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?int $navigationSort = 26;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.categories');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.categories');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.category');
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function tree(Tree $tree): Tree
    {
        return $tree
            ->maxDepth(10)
            ->fields([
                TextField::make('name'),
                IconField::make('is_active'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\TreeCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
