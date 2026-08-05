<?php

namespace App\Filament\Tenant\Resources\CashDrawers;

use App\Filament\Concerns\RequiresTenantModule;
use App\Filament\Tenant\Resources\CashDrawers\Pages\CreateCashDrawer;
use App\Filament\Tenant\Resources\CashDrawers\Pages\EditCashDrawer;
use App\Filament\Tenant\Resources\CashDrawers\Pages\ListCashDrawers;
use App\Filament\Tenant\Resources\CashDrawers\Schemas\CashDrawerForm;
use App\Filament\Tenant\Resources\CashDrawers\Tables\CashDrawersTable;
use App\Models\Tenant\CashDrawer;
use App\Support\Modules\TenantModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashDrawerResource extends Resource
{
    use RequiresTenantModule;

    protected static ?string $model = CashDrawer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArchiveBox;

    protected static ?int $navigationSort = 399;

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
        return __('commerce.resources.cash_drawers');
    }

    public static function getPluralModelLabel(): string
    {
        return __('commerce.resources.cash_drawers');
    }

    public static function getModelLabel(): string
    {
        return __('commerce.resources.cash_drawer');
    }

    public static function form(Schema $schema): Schema
    {
        return CashDrawerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashDrawersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashDrawers::route('/'),
            'create' => CreateCashDrawer::route('/create'),
            'edit' => EditCashDrawer::route('/{record}/edit'),
        ];
    }
}
