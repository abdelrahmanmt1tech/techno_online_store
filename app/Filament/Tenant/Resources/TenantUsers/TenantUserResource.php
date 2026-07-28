<?php

namespace App\Filament\Tenant\Resources\TenantUsers;

use App\Filament\Tenant\Resources\TenantUsers\Pages\CreateTenantUser;
use App\Filament\Tenant\Resources\TenantUsers\Pages\EditTenantUser;
use App\Filament\Tenant\Resources\TenantUsers\Pages\ListTenantUsers;
use App\Filament\Tenant\Resources\TenantUsers\Schemas\TenantUserForm;
use App\Filament\Tenant\Resources\TenantUsers\Tables\TenantUsersTable;
use App\Models\TenantUser;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TenantUserResource extends Resource
{
    protected static ?string $model = TenantUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?int $navigationSort = 80;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.tenant_users');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.tenant_users');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.tenant_user');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('tenant-users.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('tenant-users.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('tenant-users.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('tenant-users.destroy');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.users_group');
    }

    public static function form(Schema $schema): Schema
    {
        return TenantUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantUsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenantUsers::route('/'),
            'create' => CreateTenantUser::route('/create'),
            'edit' => EditTenantUser::route('/{record}/edit'),
        ];
    }
}
