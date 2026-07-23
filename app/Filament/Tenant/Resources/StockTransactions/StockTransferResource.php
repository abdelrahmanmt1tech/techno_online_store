<?php

namespace App\Filament\Tenant\Resources\StockTransactions;

use App\Filament\Tenant\Resources\StockTransactions\Pages\CreateStockTransfer;
use App\Filament\Tenant\Resources\StockTransactions\Pages\EditStockTransfer;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ListStockTransfers;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ViewStockTransfer;
use App\Filament\Tenant\Resources\StockTransactions\Schemas\StockTransferForm;
use App\Filament\Tenant\Resources\StockTransactions\Tables\StockTransactionsTable;
use App\Models\Tenant\StockTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static ?int $navigationSort = 312;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.stock_transfers');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.stock_transfers');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.stock_transfer');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('transaction_type', ['transfer']);
    }

    public static function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransactionsTable::configure($table, showTypeFilter: false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockTransfers::route('/'),
            'create' => CreateStockTransfer::route('/create'),
            'view' => ViewStockTransfer::route('/{record}'),
            'edit' => EditStockTransfer::route('/{record}/edit'),
        ];
    }
}
