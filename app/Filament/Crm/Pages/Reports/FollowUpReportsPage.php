<?php

namespace App\Filament\Crm\Pages\Reports;

use App\Filament\Crm\CrmPage;
use App\Filament\Crm\Exports\FollowUpReportExporter;
use App\Filament\Crm\Pages\Reports\Concerns\InteractsWithCrmReportPage;
use App\Filament\Crm\Pages\Reports\Widgets\FollowUpReportEmployeeChart;
use App\Filament\Crm\Pages\Reports\Widgets\FollowUpReportStatsWidget;
use App\Filament\Crm\Pages\Reports\Widgets\FollowUpReportTypeChart;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Client;
use App\Models\Tenant\FollowUpStatus;
use App\Models\Tenant\FollowUpType;
use App\Models\Tenant\LeadSource;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Services\Crm\Reports\CrmReportFilters;
use App\Services\Crm\Reports\FollowUpReportQuery;
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

class FollowUpReportsPage extends CrmPage implements HasTable
{
    use InteractsWithCrmReportPage;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::PhoneArrowUpRight;

    protected static ?int $navigationSort = 23;

    protected string $view = 'filament.crm.pages.reports.follow-up-reports';

    public static function getNavigationLabel(): string
    {
        return __('crm.reports.followup.navigation');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('crm.nav.reports');
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof User && CrmReportAccess::canViewFollowUpReports($user);
    }

    public function getTitle(): string
    {
        return __('crm.reports.followup.title');
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummaryProperty(): array
    {
        return $this->cachedReportSummary(fn (): array => FollowUpReportQuery::summary(
            $this->currentUser(),
            CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'scheduled_at'),
        ));
    }

    /**
     * @return array<int, class-string>
     */
    public function getHeaderWidgets(): array
    {
        return [
            FollowUpReportStatsWidget::class,
            FollowUpReportTypeChart::class,
            FollowUpReportEmployeeChart::class,
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
                ->url(fn (): string => $this->buildPrintUrl('crm.reports.followups.print'))
                ->openUrlInNewTab(),
        ];
    }

    public function table(Table $table): Table
    {
        $headerActions = [];
        if (CrmReportAccess::canExportFollowUpReports($this->currentUser())) {
            $headerActions[] = ExportAction::make()
                ->label(__('crm.reports.actions.export'))
                ->exporter(FollowUpReportExporter::class)
                ->fileName(fn (): string => 'follow-up-reports-'.now()->format('Y-m-d-His'));
        }

        return $table
            ->query(fn (): Builder => FollowUpReportQuery::tableQuery(
                $this->currentUser(),
                CrmReportFilters::fromTableFilters($this->tableFilters ?? [], 'scheduled_at'),
            ))
            ->headerActions($headerActions)
            ->columns([
                TextColumn::make('scheduled_at')
                    ->label(__('crm.fields.scheduled_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label(__('crm.fields.completed_at'))
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('-'),
                TextColumn::make('followUpType.name')
                    ->label(__('crm.fields.follow_up_type'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('followUpStatus.name')
                    ->label(__('crm.fields.follow_up_status'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('assignedTo.name')
                    ->label(__('crm.fields.assigned_to'))
                    ->placeholder('-'),
                TextColumn::make('opportunity.title')
                    ->label(__('crm.fields.opportunity'))
                    ->limit(30),
                TextColumn::make('opportunity.client.name')
                    ->label(__('crm.fields.client'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('opportunity.branch.name')
                    ->label(__('crm.fields.branch'))
                    ->formatStateUsing(fn ($state) => is_array($state)
                        ? ($state[app()->getLocale()] ?? reset($state) ?: '-')
                        : (string) ($state ?? '-')),
                TextColumn::make('scheduling_state')
                    ->label(__('crm.reports.followup.columns.scheduling_state'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('crm.reports.followup.scheduling.'.$state)),
            ])
            ->defaultSort('scheduled_at', 'desc')
            ->filters([
                Filter::make('date_range')
                    ->label(__('crm.reports.filters.date_range'))
                    ->schema([
                        Select::make('basis')
                            ->label(__('crm.reports.filters.date_basis'))
                            ->options([
                                'scheduled_at' => __('crm.reports.filters.basis_scheduled_at'),
                                'completed_at' => __('crm.reports.filters.basis_completed_at'),
                                'created_at' => __('crm.reports.filters.basis_created_at'),
                            ])
                            ->default('scheduled_at'),
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
                SelectFilter::make('follow_up_type_id')
                    ->label(__('crm.fields.follow_up_type'))
                    ->options(fn (): array => FollowUpType::query()->pluck('name', 'id')->map(
                        fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name,
                    )->all())
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('follow_up_status_id')
                    ->label(__('crm.fields.follow_up_status'))
                    ->options(fn (): array => FollowUpStatus::query()->pluck('name', 'id')->map(
                        fn ($name) => is_array($name) ? ($name[app()->getLocale()] ?? reset($name)) : $name,
                    )->all())
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('follow_up_scheduling')
                    ->label(__('crm.reports.followup.filters.scheduling'))
                    ->options([
                        'scheduled' => __('crm.reports.followup.scheduling.scheduled'),
                        'overdue' => __('crm.reports.followup.scheduling.overdue'),
                        'completed' => __('crm.reports.followup.scheduling.completed'),
                    ])
                    ->query(fn (Builder $query): Builder => $query),
                SelectFilter::make('opportunity_id')
                    ->label(__('crm.fields.opportunity'))
                    ->relationship('opportunity', 'title')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn (Opportunity $record): string => (string) ($record->title ?? $record->id)),
                SelectFilter::make('client_id')
                    ->label(__('crm.fields.client'))
                    ->options(fn (): array => Client::query()->limit(100)->pluck('name', 'id')->map(
                        fn ($name, $id) => is_array($name)
                            ? ($name[app()->getLocale()] ?? reset($name) ?: (string) $id)
                            : (string) ($name ?? $id),
                    )->all())
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
