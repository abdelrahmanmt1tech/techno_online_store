<?php

namespace App\Filament\Resources\Themes;

use App\Filament\Resources\Themes\Pages\CreateTheme;
use App\Filament\Resources\Themes\Pages\EditTheme;
use App\Filament\Resources\Themes\Pages\ListThemes;
use App\Filament\Resources\Themes\Schemas\ThemeForm;
use App\Filament\Resources\Themes\Tables\ThemesTable;
use App\Models\Theme;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ThemeResource extends Resource
{
    protected static ?string $model = Theme::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Swatch;

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.themes');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.themes');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.theme');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.nav_site_content_group');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('themes.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('themes.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('themes.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('themes.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return ThemeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ThemesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListThemes::route('/'),
            'create' => CreateTheme::route('/create'),
            'edit' => EditTheme::route('/{record}/edit'),
        ];
    }
}
