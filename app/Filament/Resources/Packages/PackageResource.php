<?php

namespace App\Filament\Resources\Packages;

use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Filament\Resources\Packages\Schemas\PackageForm;
use App\Filament\Resources\Packages\Tables\PackagesTable;
use App\Models\Package;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentList;

    protected static ?int $navigationSort = 51;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.packages');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.packages');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.package');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('plans.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('plans.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('plans.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('plans.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return PackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PackagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}
