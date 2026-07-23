<?php

namespace App\Filament\Tenant\Resources\StockTransactions;

use App\Filament\Tenant\Resources\StockTransactions\Pages\CreateStockReceipt;
use App\Filament\Tenant\Resources\StockTransactions\Pages\EditStockReceipt;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ListStockReceipts;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ViewStockReceipt;
use App\Filament\Tenant\Resources\StockTransactions\Schemas\StockReceiptForm;
use App\Filament\Tenant\Resources\StockTransactions\Tables\StockTransactionsTable;
use App\Models\Tenant\StockTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockReceiptResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowDownTray;

    protected static ?int $navigationSort = 310;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.stock_receipts');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.stock_receipts');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.stock_receipt');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('transaction_type', ['opening_balance', 'manual_receipt']);
    }

    public static function form(Schema $schema): Schema
    {
        return StockReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransactionsTable::configure($table, showTypeFilter: false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockReceipts::route('/'),
            'create' => CreateStockReceipt::route('/create'),
            'view' => ViewStockReceipt::route('/{record}'),
            'edit' => EditStockReceipt::route('/{record}/edit'),
        ];
    }
}
