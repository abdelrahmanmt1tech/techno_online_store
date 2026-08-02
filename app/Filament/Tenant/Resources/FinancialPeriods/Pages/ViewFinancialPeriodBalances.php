<?php

namespace App\Filament\Tenant\Resources\FinancialPeriods\Pages;

use App\Filament\Tenant\Resources\FinancialPeriods\FinancialPeriodResource;
use App\Models\Tenant\AccountPeriodBalance;
use App\Models\Tenant\AccountTree;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewFinancialPeriodBalances extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = FinancialPeriodResource::class;

    protected string $view = 'filament.resources.financial-periods.pages.view-financial-period-balances';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return __('dashboard.resources.financial_period.balances_title', [
            'period' => $this->getRecord()->name,
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AccountPeriodBalance::query()
                ->with('accountTree')
                ->where('financial_period_id', $this->getRecord()->id))
            ->columns([
                TextColumn::make('accountTree.account_code')
                    ->label(__('dashboard.resources.financial_period.account_code'))
                    ->toggleable(),
                TextColumn::make('accountTree.account_name')
                    ->label(__('dashboard.resources.financial_period.account'))
                    ->searchable(),
                TextColumn::make('opening_debit')
                    ->label(__('dashboard.resources.financial_period.opening_debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('danger'),
                TextColumn::make('opening_credit')
                    ->label(__('dashboard.resources.financial_period.opening_credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success'),
                TextColumn::make('movement_debit')
                    ->label(__('dashboard.resources.financial_period.movement_debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->toggleable(),
                TextColumn::make('movement_credit')
                    ->label(__('dashboard.resources.financial_period.movement_credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->toggleable(),
                TextColumn::make('closing_debit')
                    ->label(__('dashboard.resources.financial_period.closing_debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('closing_credit')
                    ->label(__('dashboard.resources.financial_period.closing_credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                TextColumn::make('net_balance')
                    ->label(__('dashboard.resources.financial_period.net_balance'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->badge(),
                TextColumn::make('balance_side')
                    ->label(__('dashboard.resources.financial_period.balance_side'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? (string) $state),
            ])
            ->filters([
                SelectFilter::make('balance_side')
                    ->label(__('dashboard.resources.financial_period.balance_side'))
                    ->options(\App\Enums\BalanceSide::options())
                    ->native(false),
                Filter::make('account_filter')
                    ->schema([
                        Select::make('account_tree_id')
                            ->label(__('dashboard.resources.financial_period.account'))
                            ->options(fn (): array => AccountTree::query()
                                ->orderBy('account_name')
                                ->pluck('account_name', 'id')
                                ->toArray())
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['account_tree_id'] ?? null, fn (Builder $q, $id) => $q->where('account_tree_id', $id))),
            ]);
    }
}
