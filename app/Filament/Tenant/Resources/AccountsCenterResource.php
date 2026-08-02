<?php

namespace App\Filament\Tenant\Resources;

use App\Filament\Tenant\Resources\AccountsCenterResource\Pages;
use App\Models\Tenant\AccountsCenter;
use App\Models\Tenant\AccountTree;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use JsonException;

class AccountsCenterResource extends Resource
{
    protected static ?string $model = AccountsCenter::class;

    protected static ?string $slug = 'accounts-centers';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = null;

    protected static ?string $pluralModelLabel = null;

    protected static ?string $modelLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.resources.accounts_center.nav');
    }

    public static function getPluralModelLabel(): string
    {
        return __('dashboard.resources.accounts_center.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('dashboard.resources.accounts_center.model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('accounts_centers.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('accounts_centers.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->can('accounts_centers.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return Auth::user()?->can('accounts_centers.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return Auth::user()?->can('accounts_centers.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return Auth::user()?->can('accounts_centers.delete') ?? false;
    }

    public static function canRestore($record): bool
    {
        return Auth::user()?->can('accounts_centers.restore') ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return Auth::user()?->can('accounts_centers.force_delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()?->can('accounts_centers.delete_bulk') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return Auth::user()?->can('accounts_centers.restore_bulk') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return Auth::user()?->can('accounts_centers.force_delete_bulk') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.resources.accounts_center.section_data'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('dashboard.resources.accounts_center.name'))
                            ->required(),
                        Select::make('account_tree_id')
                            ->label(__('dashboard.resources.accounts_center.account_tree'))
                            ->options(fn (): array => AccountTree::query()->orderBy('account_name')->pluck('account_name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Toggle::make('move_entries_to_new_tree')
                            ->label(__('dashboard.resources.accounts_center.move_entries_to_new_tree'))
                            ->helperText(__('dashboard.resources.accounts_center.move_entries_to_new_tree_help'))
                            ->default(true)
                            ->visibleOn('edit')
                            ->dehydrated(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('accountTree')->withSum('movements as profit_total', 'amount'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.resources.accounts_center.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('debit')
                    ->label(__('dashboard.pages.account_statement.sum_debit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success')
                    ->sortable(),
                TextColumn::make('credit')
                    ->label(__('dashboard.pages.account_statement.sum_credit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('net_balance')
                    ->label(__('dashboard.pages.account_statement.sum_balance'))
                    ->getStateUsing(fn (AccountsCenter $record): float => round((float) ($record->debit ?? 0) - (float) ($record->credit ?? 0), 2))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color(fn (AccountsCenter $record): string => ((float) ($record->debit ?? 0) - (float) ($record->credit ?? 0)) >= 0 ? 'primary' : 'danger')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        $dir = strtolower($direction) === 'asc' ? 'asc' : 'desc';

                        return $query->orderByRaw("(COALESCE(debit, 0) - COALESCE(credit, 0)) {$dir}");
                    }),
                TextColumn::make('profit_total')
                    ->label(__('dashboard.resources.accounts_center.profit_total'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('accountTree.account_name')
                    ->label(__('dashboard.resources.accounts_center.account_tree'))
                    ->placeholder('—'),
            ])
            ->filters([
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label(__('dashboard.pages.accounts_centers_report.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.accounts_centers_report.to_date')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $column = \Illuminate\Support\Facades\Schema::hasColumn('accounts_center_movements', 'movement_date')
                            ? 'movement_date'
                            : 'created_at';

                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereHas(
                                    'movements',
                                    fn (Builder $mq) => $mq->whereDate($column, '>=', $date)
                                ),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereHas(
                                    'movements',
                                    fn (Builder $mq) => $mq->whereDate($column, '<=', $date)
                                ),
                            );
                    }),
                TrashedFilter::make(),
            ])
            ->headerActions([
                Action::make('printReport')
                    ->label(__('dashboard.pages.account_statement.print_report'))
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->visible(fn (): bool => \Illuminate\Support\Facades\Route::has('reports.accounts-centers.print'))
                    ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.view') ?? false)
                    ->url(fn (): string => static::getAccountsCenterPrintUrl())
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.update') ?? false),
                DeleteAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.delete') ?? false),
                RestoreAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.restore') ?? false),
                ForceDeleteAction::make()
                    ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.force_delete') ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.delete_bulk') ?? false),
                    RestoreBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.restore_bulk') ?? false),
                    ForceDeleteBulkAction::make()
                        ->authorize(fn (): bool => Auth::user()?->can('accounts_centers.force_delete_bulk') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountsCenters::route('/'),
            'create' => Pages\CreateAccountsCenter::route('/create'),
            'edit' => Pages\EditAccountsCenter::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getAccountsCenterPrintUrl(): string
    {
        $user = Auth::user();

        $payload = [
            'printed_by' => $user?->name ?? $user?->email ?? '-',
            'printed_by_id' => $user?->id,
            'locale' => app()->getLocale(),
        ];

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '#';
        }

        $token = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');

        return route('reports.accounts-centers.print', ['p' => $token]);
    }
}
