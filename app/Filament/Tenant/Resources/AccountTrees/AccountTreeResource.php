<?php

namespace App\Filament\Tenant\Resources\AccountTrees;

use App\Filament\Tenant\Resources\AccountTrees\Pages\CreateAccountTree;
use App\Filament\Tenant\Resources\AccountTrees\Pages\EditAccountTree;
use App\Filament\Tenant\Resources\AccountTrees\Pages\ListAccountTrees;
use App\Filament\Tenant\Resources\AccountTrees\Pages\TreeAccountTree;
use App\Filament\Tenant\Resources\AccountTrees\Pages\ViewAccountTree;
use App\Filament\Tenant\Resources\AccountTrees\Schemas\AccountTreeForm;
use App\Filament\Tenant\Resources\AccountTrees\Schemas\AccountTreeInfolist;
use App\Filament\Tenant\Resources\AccountTrees\Tables\AccountTreesTable;
use App\Models\Tenant\AccountTree;
use App\Models\Tenant\Entry;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Openplain\FilamentTreeView\Fields\IconField;
use Openplain\FilamentTreeView\Fields\TextField;
use Openplain\FilamentTreeView\Tree;
use Filament\Actions\Action;
use App\Filament\Tenant\Pages\Accounting\AccountTreeStatementPage;
use App\Filament\Tenant\Pages\Accounting\AssistantLedger;
use Illuminate\Support\Facades\Auth;

class AccountTreeResource extends Resource
{
    protected static ?string $model = AccountTree::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;



    protected static ?string $navigationLabel = null;
    protected static ?string $pluralModelLabel = null;
    protected static ?string $modelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.account_tree.nav');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.account_tree.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.account_tree.model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('account_trees.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('account_trees.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('account_trees.view') ?? false;
    }

    public static function canView($record): bool
    {
        return Auth::user()?->can('account_trees.show') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('account_trees.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('account_trees.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('account_trees.delete') ?? false;
    }

    public static function canRestore($record): bool
    {
        return Auth::user()?->can('account_trees.restore') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return Auth::user()?->can('account_trees.force_delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->can('account_trees.delete_bulk') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()?->can('account_trees.restore_bulk') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()?->can('account_trees.force_delete_bulk') ?? false;
    }

    protected static ?string $recordTitleAttribute = 'account_name';

    public static function form(Schema $schema): Schema
    {
        return AccountTreeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AccountTreeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountTreesTable::configure($table);
    }

    public static function tree(Tree $tree): Tree
    {
        return $tree
            ->fields([
                TextField::make('account_name')
                    ->color('primary' ) //| 'gray' | 'success' | 'warning' | 'danger'

                ,

//                TextField::make('account_code'),
//                TextField::make('account_type')
//                    ->formatStateUsing(fn($state) => match ($state) {
//                        'debit' => "debit",
//                        'credit' => "credit",
//                        default =>$state
//                    }),
//

//                IconField::make('is_active'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('account_trees.show') ?? false),
                EditAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('account_trees.update') ?? false),
                Action::make('toggle_disable')
                    ->label(fn ($record): string => $record->is_disabled
                        ? __('dashboard.resources.account_tree.enable_account')
                        : __('dashboard.resources.account_tree.disable_account'))
                    ->icon(fn ($record): string => $record->is_disabled ? 'heroicon-o-play' : 'heroicon-o-pause')
                    ->color(fn ($record): string => $record->is_disabled ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->authorize(fn (): bool => Auth::user()?->can('account_trees.disable') ?? false)
                    ->action(function ($record): void {
                        $record->update(['is_disabled' => ! ((bool) $record->is_disabled)]);
                    }),
                Action::make('delete_with_mode')
                    ->label(__('dashboard.pages.account_tree_cleanup.delete_action'))
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->authorize(fn (): bool => Auth::user()?->can('account_trees.delete') ?? false)
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

                            // Delete only this node without descendant cascade.
                            AccountTree::query()->whereKey($record->id)->delete();
                        });
                    }),
                Action::make('assistant_ledger')
                ->label(__('dashboard.resources.account_tree.assistant_ledger'))
                ->icon('heroicon-o-book-open')
                ->url(fn ($record) => AssistantLedger::getUrl([
                    'account_tree_id' => $record->id,
                ]))
                ->authorize(fn (): bool => Auth::user()?->can('account_trees.assistant_ledger') ?? false),
                Action::make('account_statement')
                    ->label(__('dashboard.resources.account_tree.account_statement'))
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => AccountTreeStatementPage::getUrl([
                        'account_tree_id' => $record->id,
                    ]))
                    ->authorize(fn (): bool => Auth::user()?->can('account_trees.statement') ?? false),
            ])
            ;
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => TreeAccountTree::route('/'),
            'create' => CreateAccountTree::route('/create'),
            'view' => ViewAccountTree::route('/{record}'),
            'edit' => EditAccountTree::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
