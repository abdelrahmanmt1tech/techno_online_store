<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountTree;
use App\Models\Tenant\AccountsCenter;
use App\Models\Tenant\Client;
use App\Models\Tenant\Entry;
use App\Models\Tenant\Supplier;
use Illuminate\Support\Facades\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AccountTreeCleanupPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.account-tree-cleanup-page';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTrash;

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.account_tree_cleanup.nav');
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.account_tree_cleanup.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('account_tree_cleanup.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('account_tree_cleanup.view') ?? false;
    }

    public function getTableRecordKey(Model|array $record): string
    {
        if (is_array($record)) {
            return (string) ($record['id'] ?? '');
        }

        return (string) ($record->id ?? '');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                $deletedClientTreeIds = Client::onlyTrashed()->whereNotNull('account_tree_id')->select('account_tree_id');
                $deletedSupplierTreeIds = Schema::hasColumn('suppliers', 'deleted_at')
                    ? Supplier::onlyTrashed()->whereNotNull('account_tree_id')->select('account_tree_id')
                    : Supplier::query()->whereRaw('0 = 1')->select('account_tree_id');
                $deletedAccountsCenterTreeIds = AccountsCenter::onlyTrashed()->whereNotNull('account_tree_id')->select('account_tree_id');

                return AccountTree::query()
                    ->where(function (Builder $query) use (
                        $deletedClientTreeIds,
                        $deletedSupplierTreeIds,
                        $deletedAccountsCenterTreeIds
                    ): Builder {
                        return $query
                            ->whereIn('id', $deletedClientTreeIds)
                            ->orWhereIn('id', $deletedSupplierTreeIds)
                            ->orWhereIn('id', $deletedAccountsCenterTreeIds);
                    })
                    ->withCount(['entries', 'subAccounts'])
                    ->orderByDesc('id');
            })
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('account_code')
                    ->label(__('dashboard.pages.account_tree_cleanup.account_code'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('account_name')
                    ->label(__('dashboard.pages.account_tree_cleanup.account_name'))
                    ->searchable(),
                TextColumn::make('entries_count')
                    ->label(__('dashboard.pages.account_tree_cleanup.entries_count'))
                    ->sortable(),
                TextColumn::make('sub_accounts_count')
                    ->label(__('dashboard.pages.account_tree_cleanup.sub_accounts_count'))
                    ->sortable(),
                TextColumn::make('linked_deleted_types')
                    ->label(__('dashboard.pages.account_tree_cleanup.deleted_link_types'))
                    ->state(fn (AccountTree $record): string => $this->deletedLinkTypesLabel($record))
                    ->wrap(),
            ])
            ->recordActions([
                Action::make('disableAccount')
                    ->label(fn (AccountTree $record): string => $record->is_disabled
                        ? __('dashboard.pages.account_tree_cleanup.enable_action')
                        : __('dashboard.pages.account_tree_cleanup.disable_action'))
                    ->icon(fn (AccountTree $record): string => $record->is_disabled ? 'heroicon-o-play' : 'heroicon-o-pause')
                    ->color(fn (AccountTree $record): string => $record->is_disabled ? 'success' : 'warning')
                    ->authorize(fn (): bool => Auth::user()?->can('account_tree_cleanup.disable') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading(fn (AccountTree $record): string => $record->is_disabled
                        ? __('dashboard.pages.account_tree_cleanup.enable_modal_heading')
                        : __('dashboard.pages.account_tree_cleanup.disable_modal_heading'))
                    ->modalDescription(fn (AccountTree $record): string => $record->is_disabled
                        ? __('dashboard.pages.account_tree_cleanup.enable_modal_description')
                        : __('dashboard.pages.account_tree_cleanup.disable_modal_description'))
                    ->action(function (AccountTree $record): void {
                        DB::transaction(function () use ($record): void {
                            $record->forceFill([
                                'is_disabled' => ! ((bool) $record->is_disabled),
                            ])->saveQuietly();
                        });

                        Notification::make()
                            ->title(__('dashboard.pages.account_tree_cleanup.disable_success'))
                            ->success()
                            ->send();
                    }),
                Action::make('cleanupDelete')
                    ->label(__('dashboard.pages.account_tree_cleanup.delete_action'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->authorize(fn (): bool => Auth::user()?->can('account_tree_cleanup.delete') ?? false)
                    ->requiresConfirmation()
                    ->modalHeading(__('dashboard.pages.account_tree_cleanup.delete_modal_heading'))
                    ->modalDescription(__('dashboard.pages.account_tree_cleanup.delete_modal_description'))
                    ->schema([
                        Radio::make('mode')
                            ->label(__('dashboard.pages.account_tree_cleanup.delete_mode'))
                            ->options([
                                'tree_only' => __('dashboard.pages.account_tree_cleanup.delete_mode_tree_only'),
                                'tree_with_entries_and_descendants' => __('dashboard.pages.account_tree_cleanup.delete_mode_tree_entries_desc'),
                            ])
                            ->default('tree_only')
                            ->required()
                            ->inline(false),
                    ])
                    ->action(function (AccountTree $record, array $data): void {
                        $mode = (string) ($data['mode'] ?? 'tree_only');

                        DB::transaction(function () use ($record, $mode): void {
                            if ($mode === 'tree_with_entries_and_descendants') {
                                $ids = $record->collectAccountIdsForTotals();
                                Entry::query()->whereIn('account_tree_id', $ids)->delete();
                                AccountTree::query()->whereIn('id', $ids)->delete();

                                return;
                            }

                            // Delete only this node (skip model event cascade to descendants).
                            AccountTree::query()->whereKey($record->id)->delete();
                        });

                        Notification::make()
                            ->title(__('dashboard.pages.account_tree_cleanup.delete_success'))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('disableSelected')
                        ->label(__('dashboard.pages.account_tree_cleanup.disable_action'))
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->authorize(fn (): bool => Auth::user()?->can('account_tree_cleanup.disable') ?? false)
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            DB::transaction(function () use ($records): void {
                                AccountTree::query()
                                    ->whereIn('id', $records->pluck('id')->all())
                                    ->update(['is_disabled' => true]);
                            });

                            Notification::make()
                                ->title(__('dashboard.pages.account_tree_cleanup.disable_success'))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('enableSelected')
                        ->label(__('dashboard.pages.account_tree_cleanup.enable_action'))
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->authorize(fn (): bool => Auth::user()?->can('account_tree_cleanup.disable') ?? false)
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            DB::transaction(function () use ($records): void {
                                AccountTree::query()
                                    ->whereIn('id', $records->pluck('id')->all())
                                    ->update(['is_disabled' => false]);
                            });

                            Notification::make()
                                ->title(__('dashboard.pages.account_tree_cleanup.disable_success'))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('cleanupDeleteBulk')
                        ->label(__('dashboard.pages.account_tree_cleanup.delete_action'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->authorize(fn (): bool => Auth::user()?->can('account_tree_cleanup.delete') ?? false)
                        ->requiresConfirmation()
                        ->schema([
                            Radio::make('mode')
                                ->label(__('dashboard.pages.account_tree_cleanup.delete_mode'))
                                ->options([
                                    'tree_only' => __('dashboard.pages.account_tree_cleanup.delete_mode_tree_only'),
                                    'tree_with_entries_and_descendants' => __('dashboard.pages.account_tree_cleanup.delete_mode_tree_entries_desc'),
                                ])
                                ->default('tree_only')
                                ->required()
                                ->inline(false),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $mode = (string) ($data['mode'] ?? 'tree_only');

                            DB::transaction(function () use ($records, $mode): void {
                                if ($mode === 'tree_with_entries_and_descendants') {
                                    $allIds = [];
                                    foreach ($records as $record) {
                                        $ids = $record instanceof AccountTree
                                            ? $record->collectAccountIdsForTotals()
                                            : [];
                                        $allIds = array_merge($allIds, $ids);
                                    }
                                    $allIds = array_values(array_unique(array_map('intval', $allIds)));

                                    if ($allIds !== []) {
                                        Entry::query()->whereIn('account_tree_id', $allIds)->delete();
                                        AccountTree::query()->whereIn('id', $allIds)->delete();
                                    }

                                    return;
                                }

                                AccountTree::query()
                                    ->whereIn('id', $records->pluck('id')->all())
                                    ->delete();
                            });

                            Notification::make()
                                ->title(__('dashboard.pages.account_tree_cleanup.delete_success'))
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    protected function deletedLinkTypesLabel(AccountTree $record): string
    {
        $types = [];

        if (Client::onlyTrashed()->where('account_tree_id', $record->id)->exists()) {
            $types[] = __('dashboard.pages.account_tree_cleanup.type_client');
        }
        if (Schema::hasColumn('suppliers', 'deleted_at')
            && Supplier::onlyTrashed()->where('account_tree_id', $record->id)->exists()) {
            $types[] = __('dashboard.pages.account_tree_cleanup.type_supplier');
        }
        if (AccountsCenter::onlyTrashed()->where('account_tree_id', $record->id)->exists()) {
            $types[] = __('dashboard.pages.account_tree_cleanup.type_accounts_center');
        }

        return $types !== [] ? implode(' + ', $types) : '-';
    }
}

