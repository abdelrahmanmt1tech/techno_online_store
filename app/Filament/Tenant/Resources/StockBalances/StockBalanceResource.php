<?php

namespace App\Filament\Tenant\Resources\StockBalances;

use App\Filament\Tenant\Resources\StockBalances\Pages\ListStockBalances;
use App\Filament\Tenant\Resources\StockBalances\Tables\StockBalancesTable;
use App\Models\Tenant\StockBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockBalanceResource extends Resource
{
    protected static ?string $model = StockBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;

    protected static ?int $navigationSort = 316;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.stock_balances');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.stock_balances');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.stock_balance');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return StockBalancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockBalances::route('/'),
        ];
    }
}
