<?php

namespace App\Filament\Tenant\Resources\CashMovements;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Tenant\Resources\CashMovements\Pages\ListCashMovements;
use App\Filament\Tenant\Resources\CashMovements\Pages\ViewCashMovement;
use App\Filament\Tenant\Resources\CashMovements\Tables\CashMovementsTable;
use App\Models\Tenant\CashMovement;
use App\Support\Modules\TenantModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashMovementResource extends Resource
{
    use RequiresTenantModule;

    protected static ?string $model = CashMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?int $navigationSort = 404;

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
        return __('commerce.resources.cash_movements');
    }

    public static function getModelLabel(): string
    {
        return __('commerce.resources.cash_movement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commerce.resources.cash_movements');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return CashMovementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashMovements::route('/'),
            'view' => ViewCashMovement::route('/{record}'),
        ];
    }
}
