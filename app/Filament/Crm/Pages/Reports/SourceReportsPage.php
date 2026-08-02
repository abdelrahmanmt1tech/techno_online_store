<?php

namespace App\Filament\Crm\Pages\Reports;

use App\Filament\Crm\CrmPage;
use App\Filament\Crm\Exports\SourceReportExporter;
use App\Filament\Crm\Pages\Reports\Concerns\InteractsWithCrmReportPage;
use App\Filament\Crm\Pages\Reports\Widgets\SourceReportStatsWidget;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Campaign;
use App\Models\Tenant\LeadSource;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\SourceReportQuery;
use App\Support\Crm\CrmReportAccess;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SourceReportsPage extends CrmPage implements HasTable
{
    use InteractsWithCrmReportPage;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Tag;

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.crm.pages.reports.source-reports';

    public static function getNavigationLabel(): string
    {
        return __('crm.reports.source.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.reports');
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser && CrmReportAccess::canViewSourceReports($user);
    }

    public function getTitle(): string
    {
        return __('crm.reports.source.title');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        return $this->cachedReportSummary(fn (): array => SourceReportQuery::summary(
            $this->currentUser(),
            CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'clients.created_at'),
        ));
    }

    /**
     * @return array<int, class-string>
     */
    public function getHeaderWidgets(): array
    {
        return [
            SourceReportStatsWidget::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaderWidgetsData(): array
    {
        return ['summary' => $this->getSummaryProperty()];
    }

    protected function getHeaderActions(): array
    {
        if (! CrmReportAccess::canPrint($this->currentUser())) {
            return [];
        }

        return [
            Action::make('printReport')
                ->label(__('crm.reports.actions.print'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (): string => $this->buildPrintUrl('crm.reports.sources.print'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        $headerActions = [];
        if (CrmReportAccess::canExportSourceReports($this->currentUser())) {
            $headerActions[] = ExportAction::make()
                ->label(__('crm.reports.actions.export'))
                ->exporter(SourceReportExporter::class)
                ->fileName(fn (): string => 'source-reports-'.now()->format('Y-m-d-His'));
        }

        return $table
            ->query(fn (): Builder => SourceReportQuery::tableQuery(
                $this->currentUser(),
                CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'clients.created_at'),
            ))
            ->headerActions($headerActions)
            ->columns([
                TextColumn::make('name')
                    ->label(__('crm.reports.source.columns.source'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '')
                        : (string) ($state ?? ''))
                    ->searchable(),
                TextColumn::make('clients_count')
                    ->label(__('crm.reports.source.columns.clients_count'))
                    ->numeric(),
                TextColumn::make('opportunities_count')
                    ->label(__('crm.reports.source.columns.opportunities_count'))
                    ->numeric(),
                TextColumn::make('open_opportunities_count')
                    ->label(__('crm.reports.source.columns.open_count'))
                    ->numeric(),
                TextColumn::make('won_opportunities_count')
                    ->label(__('crm.reports.source.columns.won_count'))
                    ->numeric(),
                TextColumn::make('lost_opportunities_count')
                    ->label(__('crm.reports.source.columns.lost_count'))
                    ->numeric(),
                TextColumn::make('amount_total')
                    ->label(__('crm.fields.amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('agreed_amount_total')
                    ->label(__('crm.fields.agreed_amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('conversion_rate')
                    ->label(__('crm.reports.source.columns.conversion_rate'))
                    ->state(function (LeadSource $record): string {
                        $closed = (int) $record->won_opportunities_count + (int) $record->lost_opportunities_count;

                        return $closed > 0
                            ? number_format(((int) $record->won_opportunities_count / $closed) * 100, 2).'%'
                            : '-';
                    }),
                TextColumn::make('average_amount')
                    ->label(__('crm.reports.source.columns.average_amount'))
                    ->state(function (LeadSource $record): string {
                        $count = (int) $record->opportunities_count;

                        return $count > 0
                            ? number_format(((float) $record->amount_total) / $count, 2)
                            : '-';
                    }),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label(__('crm.reports.filters.date_range'))
                    ->schema([
                        Select::make('basis')
                            ->label(__('crm.reports.filters.date_basis'))
                            ->options([
                                'clients.created_at' => __('crm.reports.filters.basis_client_created_at'),
                                'created_at' => __('crm.reports.filters.basis_opportunity_created_at'),
                            ])
                            ->default('clients.created_at'),
                        DatePicker::make('from')
                            ->label(__('crm.reports.filters.from_date')),
                        DatePicker::make('to')
                            ->label(__('crm.reports.filters.to_date')),
                    ])
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('branch_id')
                    ->label(__('crm.fields.branch'))
                    ->options(fn (): array => Branch::query()->pluck('name', 'id')->map(
                        fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name,
                    )->all())
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('sales_rep_id')
                    ->label(__('crm.fields.assigned_to'))
                    ->options(fn (): array => TenantUser::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query): Builder => $query),
                $this->clientStageFilter(),
                SelectFilter::make('campaign_id')
                    ->label(__('crm.fields.campaign'))
                    ->options(fn (): array => Campaign::query()->pluck('name', 'id')->map(
                        fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name,
                    )->all())
                    ->query(fn (Builder $query): Builder => $query),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->paginated([10, 25, 50]);
    }
}
