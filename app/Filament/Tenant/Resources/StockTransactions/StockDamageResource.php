<?php

namespace App\Filament\Tenant\Resources\StockTransactions;

use App\Filament\Tenant\Resources\StockTransactions\Pages\CreateStockDamage;
use App\Filament\Tenant\Resources\StockTransactions\Pages\EditStockDamage;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ListStockDamages;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ViewStockDamage;
use App\Filament\Tenant\Resources\StockTransactions\Schemas\StockDamageForm;
use App\Filament\Tenant\Resources\StockTransactions\Tables\StockTransactionsTable;
use App\Models\Tenant\StockTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StockDamageResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ExclamationTriangle;

    protected static ?int $navigationSort = 314;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.stock_damages');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.stock_damages');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.stock_damage');
    }

    public static function canViewAny(): bool
    {
        return tenant_module_any_enabled(\App\Support\Modules\TenantModule::Store, \App\Support\Modules\TenantModule::Pos) && (Auth::user()->can('erp.stock_damages.view'));
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('erp.stock_damages.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('erp.stock_damages.manage');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('erp.stock_damages.manage');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('transaction_type', ['damage']);
    }

    public static function form(Schema $schema): Schema
    {
        return StockDamageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransactionsTable::configure($table, showTypeFilter: false, permissionKey: 'erp.stock_damages.manage');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockDamages::route('/'),
            'create' => CreateStockDamage::route('/create'),
            'view' => ViewStockDamage::route('/{record}'),
            'edit' => EditStockDamage::route('/{record}/edit'),
        ];
    }
}
