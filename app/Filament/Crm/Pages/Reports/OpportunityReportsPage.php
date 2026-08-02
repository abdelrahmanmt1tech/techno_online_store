<?php

namespace App\Filament\Crm\Pages\Reports;

use App\Filament\Crm\CrmPage;
use App\Filament\Crm\Exports\OpportunityReportExporter;
use App\Filament\Crm\Pages\Reports\Concerns\InteractsWithCrmReportPage;
use App\Filament\Crm\Pages\Reports\Widgets\OpportunityReportStageChart;
use App\Filament\Crm\Pages\Reports\Widgets\OpportunityReportStatsWidget;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Tenant\LeadSource;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\OpportunityReportQuery;
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

class OpportunityReportsPage extends CrmPage implements HasTable
{
    use InteractsWithCrmReportPage;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Briefcase;

    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.crm.pages.reports.opportunity-reports';

    public static function getNavigationLabel(): string
    {
        return __('crm.reports.opportunity.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.reports');
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser && CrmReportAccess::canViewOpportunityReports($user);
    }

    public function getTitle(): string
    {
        return __('crm.reports.opportunity.title');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        return $this->cachedReportSummary(fn (): array => OpportunityReportQuery::summary(
            $this->currentUser(),
            CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'created_at'),
        ));
    }

    /**
     * @return array<int, class-string>
     */
    public function getHeaderWidgets(): array
    {
        return [
            OpportunityReportStatsWidget::class,
            OpportunityReportStageChart::class,
        ];
    }

    /**
     * Flat props shared with every header widget. Filament's widgets component spreads this
     * array into each widget, so both widgets expose a matching public `summary` property.
     *
     * @return array<string, mixed>
     */
    public function getHeaderWidgetsData(): array
    {
        return [
            'summary' => $this->getSummaryProperty(),
        ];
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
                ->url(fn (): string => $this->buildPrintUrl('crm.reports.opportunities.print'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        $headerActions = [];
        if (CrmReportAccess::canExportOpportunityReports($this->currentUser())) {
            $headerActions[] = ExportAction::make()
                ->label(__('crm.reports.actions.export'))
                ->exporter(OpportunityReportExporter::class)
                ->fileName(fn (): string => 'opportunity-reports-'.now()->format('Y-m-d-His'));
        }

        return $table
            ->query(fn (): Builder => OpportunityReportQuery::tableQuery(
                $this->currentUser(),
                CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'created_at'),
            ))
            ->headerActions($headerActions)
            ->columns([
                TextColumn::make('title')
                    ->label(__('crm.fields.title'))
                    ->searchable()
                    ->limit(40),
                TextColumn::make('client.name')
                    ->label(__('crm.fields.client'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('client.leadSource.name')
                    ->label(__('crm.fields.source'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('campaign.name')
                    ->label(__('crm.fields.campaign'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('branch.name')
                    ->label(__('crm.fields.branch'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('amount')
                    ->label(__('crm.fields.amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('agreed_amount')
                    ->label(__('crm.fields.agreed_amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('opportunityStage.name')
                    ->label(__('crm.fields.stage'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('assignedTo.name')
                    ->label(__('crm.fields.assigned_to'))
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime('Y-m-d')
                    ->sortable(),
                TextColumn::make('closed_at')
                    ->label(__('crm.fields.closed_at'))
                    ->dateTime('Y-m-d')
                    ->placeholder('-'),
                TextColumn::make('close_duration_days')
                    ->label(__('crm.reports.opportunity.columns.close_duration'))
                    ->state(fn (Opportunity $record): string => ($days = OpportunityReportQuery::closeDurationDays($record)) !== null
                        ? (string) $days
                        : '-'),
                TextColumn::make('last_follow_up_at')
                    ->label(__('crm.reports.opportunity.columns.last_follow_up'))
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-'),
                TextColumn::make('opportunity_follow_ups_count')
                    ->label(__('crm.reports.opportunity.columns.follow_ups_count'))
                    ->numeric(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->label(__('crm.reports.filters.date_range'))
                    ->schema([
                        Select::make('basis')
                            ->label(__('crm.reports.filters.date_basis'))
                            ->options([
                                'created_at' => __('crm.reports.filters.basis_created_at'),
                                'closed_at' => __('crm.reports.filters.basis_closed_at'),
                            ])
                            ->default('created_at'),
                        DatePicker::make('from')
                            ->label(__('crm.reports.filters.from_date')),
                        DatePicker::make('to')
                            ->label(__('crm.reports.filters.to_date')),
                    ])
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('opportunity_stage_id')
                    ->label(__('crm.fields.stage'))
                    ->relationship('opportunityStage', 'name'),
                SelectFilter::make('opportunity_status')
                    ->label(__('crm.reports.filters.opportunity_status'))
                    ->options([
                        'open' => __('crm.reports.filters.status_open'),
                        'won' => __('crm.reports.filters.status_won'),
                        'lost' => __('crm.reports.filters.status_lost'),
                    ])
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('sales_rep_id')
                    ->label(__('crm.fields.assigned_to'))
                    ->relationship('assignedTo', 'name'),
                SelectFilter::make('lead_source_id')
                    ->label(__('crm.fields.source'))
                    ->options(fn (): array => LeadSource::query()->pluck('name', 'id')->map(
                        fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name,
                    )->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('client', fn (Builder $client) => $client->where('lead_source_id', $data['value']))
                        : $query),
                SelectFilter::make('campaign_id')
                    ->label(__('crm.fields.campaign'))
                    ->relationship('campaign', 'name'),
                SelectFilter::make('branch_id')
                    ->label(__('crm.fields.branch'))
                    ->options(fn (): array => Branch::query()->pluck('name', 'id')->map(
                        fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name,
                    )->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('branch_id', $data['value'])
                        : $query),
                SelectFilter::make('client_id')
                    ->label(__('crm.fields.client'))
                    ->relationship('client', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Client $record): string => is_array($record->name)
                        ? ($record->name[app()->getLocale()] ?? reset($record->name) ?: (string) $record->id)
                        : (string) ($record->name ?? $record->id)),
                $this->amountRangeFilter(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->paginated([10, 25, 50]);
    }
}
