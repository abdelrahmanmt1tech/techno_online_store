<?php

namespace App\Filament\Tenant\Resources\StockTransactions;

use App\Filament\Tenant\Resources\StockTransactions\Pages\CreateStockIssue;
use App\Filament\Tenant\Resources\StockTransactions\Pages\EditStockIssue;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ListStockIssues;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ViewStockIssue;
use App\Filament\Tenant\Resources\StockTransactions\Schemas\StockIssueForm;
use App\Filament\Tenant\Resources\StockTransactions\Tables\StockTransactionsTable;
use App\Models\Tenant\StockTransaction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockIssueResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowUpTray;

    protected static ?int $navigationSort = 311;

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.inventory');
    }

    public static function getNavigationLabel(): string
    {
        return __('erp.resources.stock_issues');
    }

    public static function getPluralModelLabel(): string
    {
        return __('erp.resources.stock_issues');
    }

    public static function getModelLabel(): string
    {
        return __('erp.resources.stock_issue');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('transaction_type', ['manual_issue']);
    }

    public static function form(Schema $schema): Schema
    {
        return StockIssueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransactionsTable::configure($table, showTypeFilter: false);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockIssues::route('/'),
            'create' => CreateStockIssue::route('/create'),
            'view' => ViewStockIssue::route('/{record}'),
            'edit' => EditStockIssue::route('/{record}/edit'),
        ];
    }
}
