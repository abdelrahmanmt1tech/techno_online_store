<?php

namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\Pages\Pages\CreatePage;
use App\Filament\Tenant\Resources\Pages\Pages\EditPage;
use App\Filament\Tenant\Resources\Pages\Pages\ListPages;
use App\Filament\Tenant\Resources\Pages\Pages\ViewPage;
use App\Filament\Tenant\Resources\Pages\Schemas\PageForm;
use App\Filament\Tenant\Resources\Pages\Tables\PagesTable;
use App\Models\Tenant\Page;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?int $navigationSort = 75;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.pages');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.page');
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
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
