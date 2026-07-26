<?php

namespace App\Filament\Resources\Blogs;

use App\Filament\Resources\Blogs\Pages\CreateBlog;
use App\Filament\Resources\Blogs\Pages\EditBlog;
use App\Filament\Resources\Blogs\Pages\ListBlogs;
use App\Filament\Resources\Blogs\Schemas\BlogForm;
use App\Filament\Resources\Blogs\Tables\BlogsTable;
use App\Models\Blog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 190;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.blogs');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.blogs');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.blog');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.nav_blog_management');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('blogs.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('blogs.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('blogs.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('blogs.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return BlogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogs::route('/'),
            'create' => CreateBlog::route('/create'),
            'edit' => EditBlog::route('/{record}/edit'),
        ];
    }
}
