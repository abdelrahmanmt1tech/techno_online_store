<?php

namespace App\Filament\Crm\Pages\Reports;

use App\Filament\Crm\CrmPage;
use App\Filament\Crm\Exports\EmployeePerformanceReportExporter;
use App\Filament\Crm\Pages\Reports\Concerns\InteractsWithCrmReportPage;
use App\Filament\Crm\Pages\Reports\Widgets\EmployeePerformanceReportStatsWidget;
use App\Models\Tenant\Branch;
use App\Models\Tenant\LeadSource;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\EmployeePerformanceReportQuery;
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

class EmployeePerformanceReportsPage extends CrmPage implements HasTable
{
    use InteractsWithCrmReportPage;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserGroup;

    protected static ?int $navigationSort = 25;

    protected string $view = 'filament.crm.pages.reports.employee-performance-reports';

    public static function getNavigationLabel(): string
    {
        return __('crm.reports.employee.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.reports');
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof User && CrmReportAccess::canViewEmployeePerformanceReports($user);
    }

    public function getTitle(): string
    {
        return __('crm.reports.employee.title');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        return $this->cachedReportSummary(fn (): array => EmployeePerformanceReportQuery::summary(
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
            EmployeePerformanceReportStatsWidget::class,
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
                ->url(fn (): string => $this->buildPrintUrl('crm.reports.employees.print'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        $headerActions = [];
        if (CrmReportAccess::canExportEmployeePerformanceReports($this->currentUser())) {
            $headerActions[] = ExportAction::make()
                ->label(__('crm.reports.actions.export'))
                ->exporter(EmployeePerformanceReportExporter::class)
                ->fileName(fn (): string => 'employee-performance-'.now()->format('Y-m-d-His'));
        }

        return $table
            ->query(fn (): Builder => EmployeePerformanceReportQuery::tableQuery(
                $this->currentUser(),
                CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'created_at'),
            ))
            ->headerActions($headerActions)
            ->columns([
                TextColumn::make('name')
                    ->label(__('crm.reports.employee.columns.employee'))
                    ->searchable(),
                TextColumn::make('clients_count')
                    ->label(__('crm.reports.employee.columns.clients'))
                    ->numeric(),
                TextColumn::make('opportunities_count')
                    ->label(__('crm.reports.employee.columns.opportunities'))
                    ->numeric(),
                TextColumn::make('open_opportunities_count')
                    ->label(__('crm.reports.employee.columns.open'))
                    ->numeric(),
                TextColumn::make('won_opportunities_count')
                    ->label(__('crm.reports.employee.columns.won'))
                    ->numeric(),
                TextColumn::make('lost_opportunities_count')
                    ->label(__('crm.reports.employee.columns.lost'))
                    ->numeric(),
                TextColumn::make('conversion_rate')
                    ->label(__('crm.reports.employee.columns.conversion_rate'))
                    ->state(fn (User $record): string => EmployeePerformanceReportQuery::conversionRate($record).'%'),
                TextColumn::make('amount_total')
                    ->label(__('crm.fields.amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('agreed_amount_total')
                    ->label(__('crm.fields.agreed_amount'))
                    ->numeric(decimalPlaces: 2),
                TextColumn::make('average_close_days')
                    ->label(__('crm.reports.employee.columns.average_close_days'))
                    ->state(fn (User $record): string => ($days = EmployeePerformanceReportQuery::averageCloseDays($record)) !== null
                        ? (string) $days
                        : '-'),
                TextColumn::make('completed_follow_ups_count')
                    ->label(__('crm.reports.employee.columns.completed_follow_ups'))
                    ->numeric(),
                TextColumn::make('overdue_follow_ups_count')
                    ->label(__('crm.reports.employee.columns.overdue_follow_ups'))
                    ->numeric(),
                TextColumn::make('effective_commissions')
                    ->label(__('crm.reports.employee.columns.effective_commissions'))
                    ->state(fn (User $record): string => EmployeePerformanceReportQuery::commissionTotalsFor($record->id)['effective']),
                TextColumn::make('net_paid')
                    ->label(__('crm.reports.employee.columns.net_paid'))
                    ->state(fn (User $record): string => EmployeePerformanceReportQuery::commissionTotalsFor($record->id)['net_paid']),
                TextColumn::make('remaining')
                    ->label(__('crm.reports.employee.columns.remaining'))
                    ->state(fn (User $record): string => EmployeePerformanceReportQuery::commissionTotalsFor($record->id)['remaining']),
            ])
            ->filters([
                Filter::make('date_range')
                    ->label(__('crm.reports.filters.date_range'))
                    ->schema([
                        Select::make('basis')
                            ->label(__('crm.reports.filters.date_basis'))
                            ->options([
                                'created_at' => __('crm.reports.filters.basis_created_at'),
                                'approved_at' => __('crm.reports.filters.basis_approved_at'),
                                'scheduled_at' => __('crm.reports.filters.basis_scheduled_at'),
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
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('sales_rep_id')
                    ->label(__('crm.fields.assigned_to'))
                    ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
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
