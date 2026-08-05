<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\Client;
use App\Models\Tenant\Entry;
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

class PartyAccountStatement extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.accounting-generic-table';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 22;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.party_account_statement.nav');
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
        return __('dashboard.pages.party_account_statement.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Entry::query()
                ->with([
                    'operation:id,date,comment,linkable_type,linkable_id',
                    'accountTree:id,account_name,account_code',
                    'linkable',
                ])
                ->whereNotNull('day_date')
                ->where(function (Builder $q): void {
                    $q->whereIn('linkable_type', [
                        Client::class,
                        Supplier::class,
                        'client',
                        'supplier',
                    ])->orWhereIn('account_tree_id', function ($sub): void {
                        $sub->select('account_tree_id')
                            ->from('customers')
                            ->whereNotNull('account_tree_id')
                            ->whereNull('deleted_at');
                    })->orWhereIn('account_tree_id', function ($sub): void {
                        $sub->select('account_tree_id')
                            ->from('suppliers')
                            ->whereNotNull('account_tree_id')
                            ->whereNull('deleted_at');
                    });
                })
                ->orderByDesc('day_date')
                ->orderByDesc('id'))
            ->columns([
                TextColumn::make('day_date')
                    ->label(__('dashboard.pages.party_account_statement.date'))
                    ->date()
                    ->sortable(),
                TextColumn::make('operation_id')
                    ->label(__('dashboard.pages.party_account_statement.operation'))
                    ->sortable(),
                TextColumn::make('party_type')
                    ->label(__('dashboard.pages.party_account_statement.party_type'))
                    ->getStateUsing(function (Entry $record): string {
                        $type = $record->linkable_type ?: $record->operation?->linkable_type;

                        return match ($type) {
                            Client::class => __('dashboard.pages.account_statement.entity_client'),
                            Supplier::class => __('dashboard.pages.account_statement.entity_supplier'),
                            default => '—',
                        };
                    }),
                TextColumn::make('party_name')
                    ->label(__('dashboard.pages.party_account_statement.party'))
                    ->getStateUsing(function (Entry $record): string {
                        if ($record->linkable) {
                            return (string) ($record->linkable->name ?? ('#'.$record->linkable_id));
                        }

                        $type = $record->operation?->linkable_type;
                        $id = $record->operation?->linkable_id;
                        if (! $type || ! $id) {
                            return '—';
                        }

                        $party = match ($type) {
                            Client::class => Client::query()->find($id),
                            Supplier::class => Supplier::query()->find($id),
                            default => null,
                        };

                        return (string) ($party?->name ?? ('#'.$id));
                    })
                    ->wrap(),
                TextColumn::make('accountTree.account_name')
                    ->label(__('dashboard.pages.party_account_statement.account'))
                    ->wrap(),
                TextColumn::make('operation.comment')
                    ->label(__('dashboard.pages.party_account_statement.description'))
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('debit')
                    ->label(__('dashboard.pages.party_account_statement.debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('danger'),
                TextColumn::make('credit')
                    ->label(__('dashboard.pages.party_account_statement.credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success'),
            ])
            ->filters([
                Filter::make('party')
                    ->form([
                        Select::make('linkable_type')
                            ->label(__('dashboard.pages.party_account_statement.party_type'))
                            ->options([
                                Client::class => __('dashboard.pages.account_statement.entity_client'),
                                Supplier::class => __('dashboard.pages.account_statement.entity_supplier'),
                            ])
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('linkable_id', null)),
                        Select::make('linkable_id')
                            ->label(__('dashboard.pages.party_account_statement.party'))
                            ->options(function (callable $get): array {
                                return match ($get('linkable_type')) {
                                    Client::class => Client::query()->orderBy('name')->pluck('name', 'id')->filter()->all(),
                                    Supplier::class => Supplier::query()->orderBy('name')->pluck('name', 'id')->filter()->all(),
                                    default => [],
                                };
                            })
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->disabled(fn (callable $get) => ! $get('linkable_type')),
                        Select::make('account_tree_id')
                            ->label(__('dashboard.pages.party_account_statement.account'))
                            ->options(function (callable $get): array {
                                $type = $get('linkable_type');
                                $id = $get('linkable_id');
                                if (! $type || ! $id) {
                                    return [];
                                }

                                $party = match ($type) {
                                    Client::class => Client::query()->find($id),
                                    Supplier::class => Supplier::query()->find($id),
                                    default => null,
                                };

                                if (! $party?->account_tree_id) {
                                    return [];
                                }

                                return [
                                    $party->account_tree_id => (string) ($party->name ?? ('#'.$party->id)),
                                ];
                            })
                            ->native(false),
                        DatePicker::make('from')->label(__('dashboard.pages.party_account_statement.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.party_account_statement.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['linkable_type'] ?? null;
                        $id = $data['linkable_id'] ?? null;
                        $accountTreeId = $data['account_tree_id'] ?? null;

                        return $query
                            ->when($type, function (Builder $q) use ($type, $id): Builder {
                                return $q->where(function (Builder $inner) use ($type, $id): void {
                                    $inner->where(function (Builder $e) use ($type, $id): void {
                                        $e->where('linkable_type', $type)
                                            ->when($id, fn (Builder $qq) => $qq->where('linkable_id', $id));
                                    })->orWhereHas('operation', function (Builder $op) use ($type, $id): void {
                                        $op->where('linkable_type', $type)
                                            ->when($id, fn (Builder $qq) => $qq->where('linkable_id', $id));
                                    });
                                });
                            })
                            ->when(
                                $accountTreeId,
                                fn (Builder $q, $treeId): Builder => $q->where('account_tree_id', $treeId),
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
            ]);
    }
}
