<?php

namespace App\Filament\Tenant\Resources\CashierSessions;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Tenant\Resources\CashierSessions\Pages\ListCashierSessions;
use App\Filament\Tenant\Resources\CashierSessions\Pages\ViewCashierSession;
use App\Filament\Tenant\Resources\CashierSessions\Schemas\CashierSessionForm;
use App\Filament\Tenant\Resources\CashierSessions\Tables\CashierSessionsTable;
use App\Models\Tenant\CashierSession;
use App\Support\Modules\TenantModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CashierSessionResource extends Resource
{
    use RequiresTenantModule;

    protected static ?string $model = CashierSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static ?int $navigationSort = 402;

    protected static function requiredTenantModules(): array
    {
        return [TenantModule::Pos];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('commerce.nav.pos');
    }

    public static function getNavigationLabel(): string
    {
        return __('commerce.resources.cashier_sessions');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commerce.resources.cashier_sessions');
    }

    public static function getModelLabel(): string
    {
        return __('commerce.resources.cashier_session');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return CashierSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashierSessionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashierSessions::route('/'),
            'view' => ViewCashierSession::route('/{record}'),
        ];
    }
}
