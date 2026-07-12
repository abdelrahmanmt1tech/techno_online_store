<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::FolderOpen;

    protected static ?int $navigationSort = 90;

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

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('categories.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('categories.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('categories.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('categories.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return CategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
