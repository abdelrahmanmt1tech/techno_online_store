<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Schemas\RoleInfolist;
use App\Filament\Resources\Roles\Tables\RolesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::LockClosed;

    protected static ?int $navigationSort = 70;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.roles_and_permissions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.roles');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.role');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.users_group');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('roles-and-permission.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('roles-and-permission.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('roles-and-permission.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('roles-and-permission.destroy');
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id';
    }
}
