<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountsCenter;
use App\Models\Tenant\AccountsCenterMovement;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Tenant\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AccountsCenterDetailsReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.accounts-center-details-report';

    protected $queryString = [
        'tableSortColumn',
        'tableSortDirection',
        'tableFilters',
    ];

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $navigationLabel = null;

    public static function getNavigationLabel(): string
    {
        return __('dashboard.pages.accounts_center_details_report.nav');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('erp.nav.accounts');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('accounts_center_details_report.view') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('accounts_center_details_report.view') ?? false;
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.accounts_center_details_report.title');
    }

    public function table(Table $table): Table
    {
        $hasMovementDate = Schema::hasColumn('accounts_center_movements', 'movement_date');
        $hasNotes = Schema::hasColumn('accounts_center_movements', 'notes');

        return $table
            ->query(function () use ($hasMovementDate): Builder {
                $query = AccountsCenterMovement::query()
                    ->with(['accountsCenter:id,name'])
                    ->whereIn('movement_type', [
                        'ticket_profit',
                        'reservation_commission',
                        'reservation_margin',
                        'manual_operation',
                    ])
                    ->orderByDesc('id');

                if ($hasMovementDate) {
                    $query->whereNotNull('movement_date')->orderByDesc('movement_date');
                }

                return $query;
            })
            ->columns([
                TextColumn::make($hasMovementDate ? 'movement_date' : 'created_at')
                    ->label(__('dashboard.pages.accounts_center_details_report.date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('accountsCenter.name')
                    ->label(__('dashboard.pages.accounts_center_details_report.account_center'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('linkable_type')
                    ->label(__('dashboard.pages.accounts_center_details_report.entity_type'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Client::class, 'App\\Models\\Client' => __('dashboard.pages.accounts_center_details_report.entity_client'),
                        Branch::class, 'App\\Models\\Branch' => __('dashboard.pages.accounts_center_details_report.entity_branch'),
                        Supplier::class, 'App\\Models\\Supplier' => __('dashboard.pages.accounts_center_details_report.entity_supplier'),
                        default => $state ?: '—',
                    })
                    ->toggleable(),

                TextColumn::make('linkable_id')
                    ->label(__('dashboard.pages.accounts_center_details_report.entity_id'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->label(__('dashboard.pages.accounts_center_details_report.profit'))
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color(fn ($state) => ((float) $state) >= 0 ? 'success' : 'danger')
                    ->sortable()
                    ->summarize(Sum::make()),

                TextColumn::make('movement_type')
                    ->label(__('dashboard.pages.accounts_center_details_report.profit_source'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'ticket_profit' => __('dashboard.pages.accounts_center_details_report.movement_ticket_profit'),
                        'reservation_commission' => __('dashboard.pages.accounts_center_details_report.movement_commission'),
                        'reservation_margin' => __('dashboard.pages.accounts_center_details_report.movement_margin'),
                        'manual_operation' => __('dashboard.pages.accounts_center_details_report.movement_manual_operation'),
                        default => $state ?: '—',
                    })
                    ->toggleable(),

                TextColumn::make($hasNotes ? 'notes' : 'id')
                    ->label(__('dashboard.pages.accounts_center_details_report.notes'))
                    ->wrap()
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible($hasNotes),
            ])
            ->filters([
                Filter::make('center')
                    ->form([
                        Select::make('accounts_center_id')
                            ->label(__('dashboard.pages.accounts_center_details_report.account_center'))
                            ->options(fn (): array => AccountsCenter::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['accounts_center_id'] ?? null, fn (Builder $q, $id) => $q->where('accounts_center_id', $id))),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('from')->label(__('dashboard.pages.accounts_center_details_report.from_date')),
                        DatePicker::make('to')->label(__('dashboard.pages.accounts_center_details_report.to_date')),
                    ])
                    ->query(function (Builder $query, array $data) use ($hasMovementDate): Builder {
                        $column = $hasMovementDate ? 'movement_date' : 'created_at';

                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate($column, '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate($column, '<=', $date),
                            );
                    }),
            ]);
    }
}
