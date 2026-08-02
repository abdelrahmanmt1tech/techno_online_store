<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Tenant\Entry;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Supplier;
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

class GeneralLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.accounting-generic-table';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static ?int $navigationSort = 21;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.general_ledger.nav');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.general_ledger.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Entry::query()
                    ->with([
                        'operation:id,date,comment,linkable_type,linkable_id',
                        'accountTree:id,account_name,account_code',
                    ])
                    ->whereNotNull('day_date')
                    ->orderByDesc('day_date')
                    ->orderByDesc('id');
            })
            ->columns([
                TextColumn::make('day_date')
                    ->label(__('dashboard.pages.general_ledger.entry_date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('operation_id')
                    ->label(__('dashboard.pages.general_ledger.entry_number'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('accountTree.account_code')
                    ->label(__('dashboard.pages.general_ledger.account_code'))
                    ->toggleable(),
                TextColumn::make('accountTree.account_name')
                    ->label(__('dashboard.pages.general_ledger.account'))
                    ->wrap()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('operation.comment')
                    ->label(__('dashboard.pages.general_ledger.description'))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('debit')
                    ->label(__('dashboard.pages.general_ledger.debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('credit')
                    ->label(__('dashboard.pages.general_ledger.credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success')
                    ->sortable(),
                TextColumn::make('operation.linkable_type')
                    ->label(__('dashboard.pages.general_ledger.entity_type'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Client::class => __('dashboard.pages.account_statement.entity_client'),
                        Supplier::class => __('dashboard.pages.account_statement.entity_supplier'),
                        Branch::class => __('dashboard.pages.account_statement.entity_branch'),
                        default => $state ?: '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('operation.linkable_id')
                    ->label(__('dashboard.pages.general_ledger.entity_id'))
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        DatePicker::make('from')->label(__('dashboard.pages.general_ledger.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.general_ledger.to_date')),
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
                Filter::make('account')
                    ->form([
                        Select::make('account_tree_id')
                            ->label(__('dashboard.pages.general_ledger.account'))
                            ->options(fn (): array => AccountTree::query()
                                ->orderBy('account_name')
                                ->pluck('account_name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['account_tree_id'] ?? null,
                            fn (Builder $q, $id): Builder => $q->where('account_tree_id', $id),
                        )),
                Filter::make('context')
                    ->form([
                        Select::make('linkable_type')
                            ->label(__('dashboard.pages.general_ledger.entity_type'))
                            ->options([
                                Client::class => __('dashboard.pages.account_statement.entity_client'),
                                Supplier::class => __('dashboard.pages.account_statement.entity_supplier'),
                                Branch::class => __('dashboard.pages.account_statement.entity_branch'),
                            ])
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('linkable_id', null)),
                        Select::make('linkable_id')
                            ->label(__('dashboard.pages.account_statement.entity'))
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
                        $type = $data['linkable_type'] ?? null;
                        $id = $data['linkable_id'] ?? null;
                        if (! $type && ! $id) {
                            return $query;
                        }

                        return $query->whereHas('operation', function (Builder $q) use ($type, $id): void {
                            $q->when($type, fn (Builder $qq) => $qq->where('linkable_type', $type))
                                ->when($id, fn (Builder $qq) => $qq->where('linkable_id', $id));
                        });
                    }),
            ]);
    }
}
