<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Pages\ViewPage;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 160;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.cms_pages');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.cms_pages');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.cms_page');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('pages.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('pages.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('pages.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('pages.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'view' => ViewPage::route('/{record}'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
