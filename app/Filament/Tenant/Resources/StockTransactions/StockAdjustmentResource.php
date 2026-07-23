<?php

namespace App\Filament\Tenant\Resources\StockTransactions;

use App\Filament\Tenant\Resources\StockTransactions\Pages\CreateStockAdjustment;
use App\Filament\Tenant\Resources\StockTransactions\Pages\EditStockAdjustment;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ListStockAdjustments;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ViewStockAdjustment;
use App\Filament\Tenant\Resources\StockTransactions\Schemas\StockAdjustmentForm;
use App\Filament\Tenant\Resources\StockTransactions\Tables\StockTransactionsTable;
use App\Models\Tenant\StockTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AdjustmentsHorizontal;

    protected static ?int $navigationSort = 313;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.stock_adjustments');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.stock_adjustments');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.stock_adjustment');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('transaction_type', ['adjustment_in', 'adjustment_out']);
    }

    public static function form(Schema $schema): Schema
    {
        return StockAdjustmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransactionsTable::configure($table, showTypeFilter: false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAdjustments::route('/'),
            'create' => CreateStockAdjustment::route('/create'),
            'view' => ViewStockAdjustment::route('/{record}'),
            'edit' => EditStockAdjustment::route('/{record}/edit'),
        ];
    }
}
