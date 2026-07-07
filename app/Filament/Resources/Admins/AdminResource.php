<?php

namespace App\Filament\Resources\Admins;

use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Filament\Resources\Admins\Schemas\AdminForm;
use App\Filament\Resources\Admins\Tables\AdminsTable;
use App\Models\Admin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AdminResource extends Resource
{
    protected static ?string $model = Admin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ShieldCheck;

    protected static ?int $navigationSort = 80;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.admins');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.admins');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.admin');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('admins.view');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('admins.create');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('admins.update');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('admins.delete');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('dashboard.users_group');
    }

    public static function form(Schema $schema): Schema
    {
        return AdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit' => EditAdmin::route('/{record}/edit'),
        ];
    }
}
