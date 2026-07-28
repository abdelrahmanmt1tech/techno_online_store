<?php

namespace App\Filament\Tenant\Resources\Governorates;

use App\Filament\Tenant\Resources\Governorates\Pages\CreateGovernorate;
use App\Filament\Tenant\Resources\Governorates\Pages\EditGovernorate;
use App\Filament\Tenant\Resources\Governorates\Pages\ListGovernorates;
use App\Filament\Tenant\Resources\Governorates\Schemas\GovernorateForm;
use App\Filament\Tenant\Resources\Governorates\Tables\GovernoratesTable;
use App\Models\Tenant\Governorate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class GovernorateResource extends Resource
{
    protected static ?string $model = Governorate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::MapPin;

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.store_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.governorates');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.governorates');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.governorate');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('governorates.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('governorates.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('governorates.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('governorates.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return GovernorateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GovernoratesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGovernorates::route('/'),
            'create' => CreateGovernorate::route('/create'),
            'edit' => EditGovernorate::route('/{record}/edit'),
        ];
    }
}
