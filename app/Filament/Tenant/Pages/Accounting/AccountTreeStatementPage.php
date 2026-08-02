<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Enums\OperationType;
use App\Filament\Tenant\Resources\Operations\OperationResource;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Entry;
use App\Models\Tenant\FinancialPeriod;
use App\Models\Tenant\Operation;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AccountTreeStatementPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.account-tree-statement';

    public ?int $accountTreeId = null;

    public ?AccountTree $accountTree = null;

    protected $queryString = [
        'tableSortColumn',
        'tableSortDirection',
        'tableFilters',
    ];

    public function mount(): void
    {
        $this->accountTreeId = request()->integer('account_tree_id') ?: null;
        $this->accountTree = $this->accountTreeId
            ? AccountTree::query()->find($this->accountTreeId)
            : null;
    }

    public function getTitle(): string
    {
        $name = $this->accountTree?->account_name;

        return $name
            ? __('dashboard.pages.account_tree_statement.title_with_name', ['name' => $name])
            : __('dashboard.pages.account_tree_statement.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_create_operation')
                ->label(__('dashboard.pages.account_tree_statement.add_operation'))
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->url(fn (): string => OperationResource::getUrl('create'))
                ->openUrlInNewTab()
                ->visible(fn (): bool => (bool) $this->accountTreeId),
            Action::make('add_operation')
                ->label(__('dashboard.pages.account_tree_statement.quick_entry'))
                ->icon('heroicon-o-bolt')
                ->color('gray')
                ->form([
                    Select::make('linkable_id')
                        ->label(__('dashboard.pages.account_tree_statement.branch'))
                        ->options(fn (): array => Branch::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required()
                        ->native(false),
                    DatePicker::make('date')
                        ->label(__('dashboard.pages.account_tree_statement.date'))
                        ->default(now())
                        ->required(),
                    Textarea::make('comment')
                        ->label(__('dashboard.pages.account_tree_statement.description'))
                        ->required()
                        ->rows(2),
                    Select::make('direction')
                        ->label(__('dashboard.pages.account_tree_statement.direction'))
                        ->options([
                            'debit' => __('dashboard.pages.account_tree_statement.this_account_debit'),
                            'credit' => __('dashboard.pages.account_tree_statement.this_account_credit'),
                        ])
                        ->required()
                        ->native(false),
                    TextInput::make('amount')
                        ->label(__('dashboard.pages.account_tree_statement.amount'))
                        ->numeric()
                        ->required()
                        ->minValue(0.01),
                    Select::make('counterpart_account_tree_id')
                        ->label(__('dashboard.pages.account_tree_statement.counterpart_account'))
                        ->options(fn (): array => AccountTree::query()->orderBy('account_name')->pluck('account_name', 'id')->all())
                        ->searchable()
                        ->required()
                        ->native(false)
                        ->helperText(__('dashboard.pages.account_tree_statement.counterpart_help')),
                ])
                ->action(function (array $data): void {
                    if (! $this->accountTreeId) {
                        Notification::make()
                            ->title(__('dashboard.pages.account_tree_statement.error_no_account'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $counterpartId = (int) $data['counterpart_account_tree_id'];
                    if ($counterpartId === $this->accountTreeId) {
                        Notification::make()
                            ->title(__('dashboard.pages.account_tree_statement.error_same_account'))
                            ->danger()
                            ->send();

                        return;
                    }

                    $amount = (float) $data['amount'];
                    $isDebit = ($data['direction'] ?? '') === 'debit';

                    DB::transaction(function () use ($data, $isDebit, $amount, $counterpartId): void {
                        $operation = Operation::create([
                            'date' => $data['date'],
                            'comment' => $data['comment'],
                            'linkable_type' => Branch::class,
                            'linkable_id' => (int) $data['linkable_id'],
                            'settlement' => false,
                            'status' => true,
                            'operation_type' => OperationType::MANUAL,
                            'is_posted' => true,
                            'posted_at' => now(),
                        ]);

                        Entry::create([
                            'operation_id' => $operation->id,
                            'account_tree_id' => $this->accountTreeId,
                            'debit' => $isDebit ? $amount : null,
                            'credit' => $isDebit ? null : $amount,
                            'day_date' => $data['date'],
                            'notes' => $data['comment'],
                        ]);

                        Entry::create([
                            'operation_id' => $operation->id,
                            'account_tree_id' => $counterpartId,
                            'debit' => $isDebit ? null : $amount,
                            'credit' => $isDebit ? $amount : null,
                            'day_date' => $data['date'],
                            'notes' => $data['comment'],
                        ]);
                    });

                    Notification::make()
                        ->title(__('dashboard.pages.account_tree_statement.operation_created'))
                        ->success()
                        ->send();

                    $this->resetTable();
                })
                ->modalWidth('md')
                ->visible(fn (): bool => (bool) $this->accountTreeId),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Entry::query()
                    ->with(['operation.entries.accountTree'])
                    ->when($this->accountTreeId, fn (Builder $q) => $q->where('account_tree_id', $this->accountTreeId))
                    ->latest('id');
            })
            ->columns([
                TextColumn::make('day_date')
                    ->label(__('dashboard.pages.account_tree_statement.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('operation_id')
                    ->label(__('dashboard.pages.account_tree_statement.entry_number'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('operation.comment')
                    ->label(__('dashboard.pages.account_tree_statement.description'))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('counterpart_accounts')
                    ->label(__('dashboard.pages.account_tree_statement.counterpart_account'))
                    ->getStateUsing(function (Entry $record): string {
                        $op = $record->operation;
                        if (! $op) {
                            return '—';
                        }
                        if (! $op->relationLoaded('entries')) {
                            $op->load(['entries.accountTree']);
                        }
                        $counterparts = $op->entries
                            ->where('account_tree_id', '!=', $record->account_tree_id)
                            ->map(fn (Entry $e) => $e->accountTree?->account_name)
                            ->filter()
                            ->unique()
                            ->values();

                        return $counterparts->isEmpty() ? '—' : $counterparts->implode(' / ');
                    })
                    ->wrap()
                    ->limit(60),
                TextColumn::make('debit')
                    ->label(__('dashboard.pages.account_tree_statement.debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('danger')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label(__('dashboard.pages.account_tree_statement.total'))
                            ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                    ]),
                TextColumn::make('credit')
                    ->label(__('dashboard.pages.account_tree_statement.credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success')
                    ->sortable()
                    ->summarize([
                        Sum::make()
                            ->label(__('dashboard.pages.account_tree_statement.total'))
                            ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ','),
                    ]),
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
                        DatePicker::make('from')->label(__('dashboard.pages.account_tree_statement.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.account_tree_statement.to_date')),
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
                SelectFilter::make('direction')
                    ->label(__('dashboard.pages.account_tree_statement.direction_filter'))
                    ->options([
                        'debit_only' => __('dashboard.pages.account_tree_statement.debit_only'),
                        'credit_only' => __('dashboard.pages.account_tree_statement.credit_only'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $v = $data['value'] ?? null;
                        if ($v === 'debit_only') {
                            return $query->whereNotNull('debit')->where('debit', '>', 0);
                        }
                        if ($v === 'credit_only') {
                            return $query->whereNotNull('credit')->where('credit', '>', 0);
                        }

                        return $query;
                    }),
            ]);
    }
}
