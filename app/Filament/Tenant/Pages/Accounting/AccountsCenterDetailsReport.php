<?php

namespace App\Filament\Tenant\Pages\Accounting;

use App\Models\Tenant\AccountsCenter;
use App\Models\Tenant\AccountsCenterMovement;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Franchise;
use App\Models\Tenant\Supplier;
use App\Filament\Exports\AccountsCenterDetailsReportExporter;
use Filament\Actions\ExportAction;
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

    public ?string $fromDate = null;
    public ?string $toDate = null;
    public ?int $accountsCenterId = null;

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
        $user = Auth::user();
        return $user?->can('accounts_center_details_report.view') ?? false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user?->can('accounts_center_details_report.view') ?? false;
    }

    public function getTitle(): string
    {
        return __('dashboard.pages.accounts_center_details_report.title');
    }

    public function mount(): void
    {
        $this->syncWidgetFiltersFromTableFilters();
    }

    public function updatedTableFilters(): void
    {
        $this->syncWidgetFiltersFromTableFilters();
        parent::updatedTableFilters();
    }

    public function getReportWidgets(): array
    {
        return [
            \App\Filament\Widgets\AccountsCenterDetailsStatsOverview::class,
        ];
    }

    protected function syncWidgetFiltersFromTableFilters(): void
    {
        $center = $this->tableFilters['center'] ?? [];
        $dateRange = $this->tableFilters['date_range'] ?? [];

        $this->accountsCenterId = filled($center['accounts_center_id'] ?? null)
            ? (int) $center['accounts_center_id']
            : null;
        $this->fromDate = $dateRange['from'] ?? null;
        $this->toDate = $dateRange['to'] ?? null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return AccountsCenterMovement::query()
                    ->with([
                        'accountsCenter:id,name',
                        'ticket:id,ticket_number_core,ticket_type_code',
                    ])
                    ->whereIn('movement_type', ['ticket_profit', 'reservation_commission', 'reservation_margin', 'manual_operation'])
                    ->whereNotNull('movement_date')
                    ->orderByDesc('movement_date')
                    ->orderByDesc('id');
            })
            ->columns([
                TextColumn::make('movement_date')
                    ->label(__('dashboard.pages.accounts_center_details_report.date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('accountsCenter.name')
                    ->label(__('dashboard.pages.accounts_center_details_report.account_center'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ticket.ticket_number_core')
                    ->label(__('dashboard.pages.accounts_center_details_report.ticket'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('linkable_type')
                    ->label(__('dashboard.pages.accounts_center_details_report.entity_type'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        Client::class => __('dashboard.pages.accounts_center_details_report.entity_client'),
                        Branch::class => __('dashboard.pages.accounts_center_details_report.entity_branch'),
                        Franchise::class => __('dashboard.pages.accounts_center_details_report.entity_franchise'),
                        Supplier::class => __('dashboard.pages.accounts_center_details_report.entity_supplier'),
                        default => $state ?: '—',
                    })
                    ->toggleable(),

                TextColumn::make('linkable_id')
                    ->label(__('dashboard.pages.accounts_center_details_report.entity_id'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->label(__('dashboard.pages.accounts_center_details_report.profit'))
                    // راجع docs/accounts-center-rfd-movements.md — تجنب Intl مع locale عربي (وهم سالب على موجب).
                    ->numeric(decimalPlaces: 2, decimalSeparator: '.', thousandsSeparator: ',')
                    ->color(fn ($state) => ((float) $state) >= 0 ? 'success' : 'danger')
                    ->sortable()
                ->summarize(Sum::make())
                ,

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

                TextColumn::make('notes')
                    ->label(__('dashboard.pages.accounts_center_details_report.notes'))
                    ->wrap()
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('movement_date', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('movement_date', '<=', $date),
                            );
                    }),
            ])
            ->headerActions([
                ExportAction::make()
                 ->label(__('dashboard.pages.account_statement.export_excel'))
                    ->exporter(AccountsCenterDetailsReportExporter::class),
            ]);
    }
}

