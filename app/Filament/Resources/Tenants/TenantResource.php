<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Resources\Tenants\Pages\EditTenant;
use App\Filament\Resources\Tenants\Pages\ListTenants;
use App\Filament\Resources\Tenants\Schemas\TenantForm;
use App\Filament\Resources\Tenants\Tables\TenantsTable;
use App\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static ?int $navigationSort = 60;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.tenants');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.tenants');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.tenant');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.settings_group');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('tenants.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('tenants.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('tenants.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('tenants.delete');
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }
}
