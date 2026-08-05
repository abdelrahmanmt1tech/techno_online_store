<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Tenant\Entry;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Supplier;
use App\Support\Modules\TenantModule;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TrialBalance extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.accounting-generic-table';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.trial_balance.nav');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function canAccess(): bool
    {
        return tenant_module_enabled(TenantModule::Accounting);
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.trial_balance.title');
    }

    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['account_tree_id'] ?? '');
        }

        return (string) ($record->account_tree_id ?? '');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Entry::query()
                    ->select([
                        'account_tree_id',
                        DB::raw('COALESCE(SUM(debit), 0) AS debit_total'),
                        DB::raw('COALESCE(SUM(credit), 0) AS credit_total'),
                    ])
                    ->with('accountTree:id,account_name,account_code')
                    ->whereNotNull('day_date')
                    ->groupBy('account_tree_id');
            })
            ->columns([
                TextColumn::make('accountTree.account_code')
                    ->label(__('dashboard.pages.trial_balance.account_code'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('accountTree.account_name')
                    ->label(__('dashboard.pages.trial_balance.account_name'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('debit_total')
                    ->label(__('dashboard.pages.trial_balance.debit_total'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('credit_total')
                    ->label(__('dashboard.pages.trial_balance.credit_total'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success')
                    ->sortable(),
                TextColumn::make('net')
                    ->label(__('dashboard.pages.trial_balance.net'))
                    ->getStateUsing(fn ($record): float => (float) ($record->debit_total ?? 0) - (float) ($record->credit_total ?? 0))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Select::make('financial_period_id')
                            ->label(__('dashboard.resources.operation.financial_period'))
                            ->options(fn (): array => FinancialPeriod::query()
                                ->orderByDesc('start_date')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->native(false),
                        DatePicker::make('from')->label(__('dashboard.pages.trial_balance.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.trial_balance.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['financial_period_id'] ?? null,
                                fn (Builder $q, $id): Builder => $q->whereHas(
                                    'operation',
                                    fn (Builder $op) => $op->where('financial_period_id', $id)
                                ),
                            )
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('day_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('day_date', '<=', $date),
                            );
                    }),
                Filter::make('context')
                    ->form([
                        Select::make('linkable_type')
                            ->label(__('dashboard.pages.trial_balance.entity_optional'))
                            ->options([
                                Client::class => __('dashboard.pages.account_statement.entity_client'),
                                Supplier::class => __('dashboard.pages.account_statement.entity_supplier'),
                                Branch::class => __('dashboard.pages.account_statement.entity_branch'),
                            ])
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('linkable_id', null)),
                        Select::make('linkable_id')
                            ->label(__('dashboard.pages.trial_balance.name'))
                            ->options(function (callable $get): array {
                                return match ($get('linkable_type')) {
                                    Client::class => Client::query()->pluck('name', 'id')->filter()->all(),
                                    Supplier::class => Supplier::query()->pluck('name', 'id')->filter()->all(),
                                    Branch::class => Branch::query()->pluck('name', 'id')->filter()->all(),
                                    default => [],
                                };
                            })
                            ->searchable()
                            ->native(false)
                            ->disabled(fn (callable $get) => ! $get('linkable_type')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['linkable_type'] ?? null,
                                fn (Builder $q, $type): Builder => $q->where('linkable_type', $type),
                            )
                            ->when(
                                $data['linkable_id'] ?? null,
                                fn (Builder $q, $id): Builder => $q->where('linkable_id', $id),
                            );
                    }),
            ])
            ->defaultSort('account_tree_id', 'asc');
    }
}
