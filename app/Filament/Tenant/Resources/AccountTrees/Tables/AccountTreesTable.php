<?php

namespace App\Filament\Tenant\Resources\AccountTrees\Tables;

use App\Filament\Pages\AccountTreeStatementPage;
use App\Filament\Pages\AssistantLedger;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AccountTreesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_name')->label(__('dashboard.resources.account_tree.account_name'))->searchable()->sortable(),
                TextColumn::make('account_code')->label(__('dashboard.resources.account_tree.account_code'))->searchable()->sortable(),
                TextColumn::make('account_type')->label(__('dashboard.resources.account_tree.account_type'))
                    ->formatStateUsing(fn($state) => match ($state) {
                        'debit' => "debit",
                        'credit' => "credit",
                    }),
                TextColumn::make('branch.name')->label(__('dashboard.resources.account_tree.branch'))->searchable()->sortable(),
                TextColumn::make('parent.account_name')->label(__('dashboard.resources.account_tree.parent_account'))->searchable()->sortable()->badge(),
                TextColumn::make('level')->label(__('dashboard.resources.account_tree.level'))->sortable()->badge(),
                TextColumn::make('income_general_statement')->label(__('dashboard.resources.account_tree.statement'))
                    ->formatStateUsing(fn($state) => match ($state) {
                        'income' => __('dashboard.resources.account_tree.statement_income'),
                        'general' => __('dashboard.resources.account_tree.statement_general'),
                        'none' => __('dashboard.resources.account_tree.statement_none'),
                    }),
                TextColumn::make('order')->label(__('dashboard.resources.account_tree.order'))->badge()->color('gray')->sortable(),
                TextColumn::make('main_acc_status')->label(__('dashboard.resources.account_tree.main_sub'))
                    ->formatStateUsing(fn($state) => match ($state) {
                    'main' => __('dashboard.resources.account_tree.main'),
                    'sub' => __('dashboard.resources.account_tree.sub'),
                        default=> $state,
                }),
                TextColumn::make('is_disabled')
                    ->label(__('dashboard.resources.account_tree.disabled_status'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('dashboard.resources.account_tree.disabled_yes')
                        : __('dashboard.resources.account_tree.disabled_no'))
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success'),


            ])
            ->filters([
                TrashedFilter::make(),
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('account_trees.delete_bulk') ?? false),
                    ForceDeleteBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('account_trees.force_delete_bulk') ?? false),
                    RestoreBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('account_trees.restore_bulk') ?? false),
                ]),
            ]);
    }
}
