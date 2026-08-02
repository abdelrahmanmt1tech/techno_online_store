<?php

namespace App\Filament\Crm\Pages\Reports;

use App\Filament\Crm\CrmPage;
use App\Filament\Crm\Exports\CampaignReportExporter;
use App\Filament\Crm\Pages\Reports\Concerns\InteractsWithCrmReportPage;
use App\Filament\Crm\Pages\Reports\Widgets\CampaignReportStatsWidget;
use App\Filament\Crm\Pages\Reports\Widgets\CampaignReportStatusChart;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Campaign;
use App\Models\Tenant\LeadSource;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CampaignReportQuery;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\CrmReportMetrics;
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

class CampaignReportsPage extends CrmPage implements HasTable
{
    use InteractsWithCrmReportPage;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Megaphone;

    protected static ?int $navigationSort = 24;

    protected string $view = 'filament.crm.pages.reports.campaign-reports';

    public static function getNavigationLabel(): string
    {
        return __('crm.reports.campaign.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.reports');
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser && CrmReportAccess::canViewCampaignReports($user);
    }

    public function getTitle(): string
    {
        return __('crm.reports.campaign.title');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        return $this->cachedReportSummary(fn (): array => CampaignReportQuery::summary(
            $this->currentUser(),
            CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'start_date'),
        ));
    }

    /**
     * @return array<int, class-string>
     */
    public function getHeaderWidgets(): array
    {
        return [
            CampaignReportStatsWidget::class,
            CampaignReportStatusChart::class,
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
                ->url(fn (): string => $this->buildPrintUrl('crm.reports.campaigns.print'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        $headerActions = [];
        if (CrmReportAccess::canExportCampaignReports($this->currentUser())) {
            $headerActions[] = ExportAction::make()
                ->label(__('crm.reports.actions.export'))
                ->exporter(CampaignReportExporter::class)
                ->fileName(fn (): string => 'campaign-reports-'.now()->format('Y-m-d-His'));
        }

        return $table
            ->query(fn (): Builder => CampaignReportQuery::tableQuery(
                $this->currentUser(),
                CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'start_date'),
            ))
            ->headerActions($headerActions)
            ->columns([
                TextColumn::make('name')
                    ->label(__('crm.reports.campaign.columns.campaign'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '')
                        : (string) ($state ?? ''))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('crm.fields.status'))
                    ->formatStateUsing(fn (string $state): string => __('crm.campaign_status_options.'.$state)),
                TextColumn::make('budget')
                    ->label(__('crm.reports.campaign.columns.budget'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('opportunities_count')
                    ->label(__('crm.reports.campaign.columns.opportunities_count'))
                    ->numeric(),
                TextColumn::make('won_opportunities_count')
                    ->label(__('crm.reports.campaign.columns.won_count'))
                    ->numeric(),
                TextColumn::make('lost_opportunities_count')
                    ->label(__('crm.reports.campaign.columns.lost_count'))
                    ->numeric(),
                TextColumn::make('amount_total')
                    ->label(__('crm.fields.amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('agreed_amount_total')
                    ->label(__('crm.fields.agreed_amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('conversion_rate')
                    ->label(__('crm.reports.campaign.columns.conversion_rate'))
                    ->state(fn (Campaign $record): string => CampaignReportQuery::conversionRate($record).'%'),
                TextColumn::make('cost_per_opportunity')
                    ->label(__('crm.reports.campaign.columns.cost_per_opportunity'))
                    ->state(fn (Campaign $record): string => CrmReportMetrics::displayValue(CampaignReportQuery::costPerOpportunity($record))),
                TextColumn::make('cost_per_won')
                    ->label(__('crm.reports.campaign.columns.cost_per_won'))
                    ->state(fn (Campaign $record): string => CrmReportMetrics::displayValue(CampaignReportQuery::costPerWonOpportunity($record))),
                TextColumn::make('expected_roi')
                    ->label(__('crm.reports.campaign.columns.expected_roi'))
                    ->state(function (Campaign $record): string {
                        $roi = CampaignReportQuery::expectedRoi($record);

                        return CrmReportMetrics::displayPercent($roi);
                    }),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label(__('crm.reports.filters.date_range'))
                    ->schema([
                        Select::make('basis')
                            ->label(__('crm.reports.filters.date_basis'))
                            ->options([
                                'start_date' => __('crm.reports.filters.basis_start_date'),
                                'created_at' => __('crm.reports.filters.basis_opportunity_created_at'),
                            ])
                            ->default('start_date'),
                        DatePicker::make('from')
                            ->label(__('crm.reports.filters.from_date')),
                        DatePicker::make('to')
                            ->label(__('crm.reports.filters.to_date')),
                    ])
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('campaign_status')
                    ->label(__('crm.fields.status'))
                    ->options([
                        'draft' => __('crm.campaign_status_options.draft'),
                        'active' => __('crm.campaign_status_options.active'),
                        'paused' => __('crm.campaign_status_options.paused'),
                        'completed' => __('crm.campaign_status_options.completed'),
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
                SelectFilter::make('lead_source_id')
                    ->label(__('crm.fields.source'))
                    ->options(fn (): array => LeadSource::query()->pluck('name', 'id')->map(
                        fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name,
                    )->all())
                    ->query(fn (Builder $query): Builder => $query),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->paginated([10, 25, 50]);
    }
}
