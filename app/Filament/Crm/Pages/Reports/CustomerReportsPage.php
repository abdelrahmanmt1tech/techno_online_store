<?php

namespace App\Filament\Crm\Pages\Reports;

use App\Enums\Crm\ClientStage;
use App\Filament\Crm\CrmPage;
use App\Filament\Crm\Exports\CustomerReportExporter;
use App\Filament\Crm\Pages\Reports\Concerns\InteractsWithCrmReportPage;
use App\Filament\Crm\Pages\Reports\Widgets\CustomerReportStageChart;
use App\Filament\Crm\Pages\Reports\Widgets\CustomerReportStatsWidget;
use App\Models\Tenant\Branch;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\CustomerReportQuery;
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

class CustomerReportsPage extends CrmPage implements HasTable
{
    use InteractsWithCrmReportPage;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Users;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.crm.pages.reports.customer-reports';

    public static function getNavigationLabel(): string
    {
        return __('crm.reports.customer.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.reports');
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof User && CrmReportAccess::canViewCustomerReports($user);
    }

    public function getTitle(): string
    {
        return __('crm.reports.customer.title');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        return $this->cachedReportSummary(fn (): array => CustomerReportQuery::summary(
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
            CustomerReportStatsWidget::class,
            CustomerReportStageChart::class,
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
                ->url(fn (): string => $this->buildPrintUrl('crm.reports.customers.print'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        $headerActions = [];
        if (CrmReportAccess::canExportCustomerReports($this->currentUser())) {
            $headerActions[] = ExportAction::make()
                ->label(__('crm.reports.actions.export'))
                ->exporter(CustomerReportExporter::class)
                ->fileName(fn (): string => 'customer-reports-'.now()->format('Y-m-d-His'));
        }

        return $table
            ->query(fn (): Builder => CustomerReportQuery::tableQuery(
                $this->currentUser(),
                CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'created_at'),
            ))
            ->headerActions($headerActions)
            ->columns([
                TextColumn::make('name')
                    ->label(__('crm.fields.client'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '')
                        : (string) ($state ?? ''))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stage')
                    ->label(__('crm.fields.stage'))
                    ->badge()
                    ->formatStateUsing(fn (ClientStage $state): string => $state->label()),
                TextColumn::make('leadSource.name')
                    ->label(__('crm.fields.source'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('salesRep.name')
                    ->label(__('crm.fields.assigned_to'))
                    ->placeholder('-'),
                TextColumn::make('opportunities_count')
                    ->label(__('crm.reports.customer.columns.opportunities_count'))
                    ->numeric(),
                TextColumn::make('won_opportunities_count')
                    ->label(__('crm.reports.customer.columns.won_opportunities_count'))
                    ->numeric(),
                TextColumn::make('opportunities_agreed_amount_total')
                    ->label(__('crm.reports.customer.columns.agreed_amount_total'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('last_follow_up_at')
                    ->label(__('crm.reports.customer.columns.last_follow_up'))
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label(__('crm.fields.created_at'))
                    ->dateTime('Y-m-d')
                    ->sortable(),
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
                                'updated_at' => __('crm.reports.filters.basis_updated_at'),
                            ])
                            ->default('created_at'),
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
                    ->query(fn (Builder $query, array $data): Builder => $query),
                $this->leadSourceFilter(),
                $this->clientStageFilter(),
                SelectFilter::make('sales_rep_id')
                    ->label(__('crm.fields.assigned_to'))
                    ->relationship('salesRep', 'name'),
                ...$this->hasOpportunitiesFilters(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->paginated([10, 25, 50]);
    }
}
