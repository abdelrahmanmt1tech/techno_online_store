<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Entry;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AssistantLedger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.assistant-ledger';

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
            ? __('dashboard.pages.assistant_ledger.title_with_name', ['name' => $name])
            : __('dashboard.pages.assistant_ledger.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Entry::query()
                    ->with('operation')
                    ->when($this->accountTreeId, fn (Builder $q) => $q->where('account_tree_id', $this->accountTreeId))
                    ->latest('id');
            })
            ->columns([
                TextColumn::make('day_date')
                    ->label(__('dashboard.pages.assistant_ledger.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('operation_id')
                    ->label(__('dashboard.pages.assistant_ledger.entry_number'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('operation.comment')
                    ->label(__('dashboard.pages.assistant_ledger.description'))
                    ->wrap()
                    ->limit(80),
                TextColumn::make('debit')
                    ->label(__('dashboard.pages.assistant_ledger.debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('credit')
                    ->label(__('dashboard.pages.assistant_ledger.credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label(__('dashboard.pages.assistant_ledger.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.assistant_ledger.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('day_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('day_date', '<=', $date),
                            );
                    }),
            ]);
    }
}
