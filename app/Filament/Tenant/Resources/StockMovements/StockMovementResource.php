<?php

namespace App\Filament\Tenant\Resources\StockMovements;

use App\Filament\Tenant\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Tenant\Resources\StockMovements\Pages\ViewStockMovement;
use App\Filament\Tenant\Resources\StockMovements\Tables\StockMovementsTable;
use App\Models\Tenant\StockMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::QueueList;

    protected static ?int $navigationSort = 315;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.stock_movements');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.stock_movements');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.stock_movement');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return StockMovementsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockMovements::route('/'),
            'view' => ViewStockMovement::route('/{record}'),
        ];
    }
}
