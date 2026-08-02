<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Filament\Exports\PeriodBalancesSnapshotExporter;
use App\Models\Tenant\AccountPeriodBalance;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\FinancialPeriod;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PeriodBalancesSnapshotReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.accounting-generic-table';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::TableCells;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.period_balances_snapshot_report.nav');
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.period_balances_snapshot_report.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('period_balances_snapshot_report.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('period_balances_snapshot_report.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AccountPeriodBalance::query()->with(['financialPeriod', 'accountTree']))
            ->columns([
                TextColumn::make('financialPeriod.name')->label(__('dashboard.resources.operation.financial_period'))->searchable(),
                TextColumn::make('accountTree.account_code')->label(__('dashboard.resources.financial_period.account_code'))->toggleable(),
                TextColumn::make('accountTree.account_name')->label(__('dashboard.resources.financial_period.account'))->searchable(),
                TextColumn::make('opening_debit')->label(__('dashboard.resources.financial_period.opening_debit')) ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('opening_credit')->label(__('dashboard.resources.financial_period.opening_credit')) ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('movement_debit')->label(__('dashboard.resources.financial_period.movement_debit')) ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('movement_credit')->label(__('dashboard.resources.financial_period.movement_credit')) ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('net_balance')->label(__('dashboard.resources.financial_period.net_balance')) ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')->badge(),
                TextColumn::make('balance_side')->label(__('dashboard.resources.financial_period.balance_side'))->badge()->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state),
            ])
            ->filters([
                SelectFilter::make('financial_period_id')
                    ->label(__('dashboard.resources.operation.financial_period'))
                    ->relationship('financialPeriod', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('balance_side')
                    ->label(__('dashboard.resources.financial_period.balance_side'))
                    ->options(\App\Enums\BalanceSide::options())
                    ->native(false),
                Filter::make('account')
                    ->schema([
                        Select::make('account_tree_id')
                            ->label(__('dashboard.resources.financial_period.account'))
                            ->options(fn (): array => AccountTree::query()->pluck('account_name', 'id')->toArray())
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['account_tree_id'] ?? null, fn (Builder $q, $id) => $q->where('account_tree_id', $id))),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label(__('dashboard.pages.account_statement.export_excel'))
                    ->exporter(PeriodBalancesSnapshotExporter::class),
            ]);
    }
}
