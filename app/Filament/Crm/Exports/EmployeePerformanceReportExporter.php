<?php

namespace App\Filament\Crm\Exports;

use App\Models\TenantUser;
use App\Services\Crm\Reports\EmployeePerformanceReportQuery;
use App\Support\Crm\CrmReportAccess;
use App\Support\Export\SafeExportColumn;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class EmployeePerformanceReportExporter extends Exporter
{
    protected static ?string $model = TenantUser::class;

    public static function getName(): string
    {
        return __('crm.reports.employee.export.name');
    }

    public static function getColumns(): array
    {
        return [
            SafeExportColumn::text('name', __('crm.reports.employee.columns.employee')),
            ExportColumn::make('clients_count')
                ->label(__('crm.reports.employee.columns.clients')),
            ExportColumn::make('opportunities_count')
                ->label(__('crm.reports.employee.columns.opportunities')),
            ExportColumn::make('open_opportunities_count')
                ->label(__('crm.reports.employee.columns.open')),
            ExportColumn::make('won_opportunities_count')
                ->label(__('crm.reports.employee.columns.won')),
            ExportColumn::make('lost_opportunities_count')
                ->label(__('crm.reports.employee.columns.lost')),
            ExportColumn::make('conversion_rate')
                ->label(__('crm.reports.employee.columns.conversion_rate'))
                ->state(fn (User $record): string => EmployeePerformanceReportQuery::conversionRate($record)),
            ExportColumn::make('amount_total')
                ->label(__('crm.fields.amount')),
            ExportColumn::make('agreed_amount_total')
                ->label(__('crm.fields.agreed_amount')),
            ExportColumn::make('average_close_days')
                ->label(__('crm.reports.employee.columns.average_close_days'))
                ->state(fn (User $record): ?float => EmployeePerformanceReportQuery::averageCloseDays($record)),
            ExportColumn::make('completed_follow_ups_count')
                ->label(__('crm.reports.employee.columns.completed_follow_ups')),
            ExportColumn::make('overdue_follow_ups_count')
                ->label(__('crm.reports.employee.columns.overdue_follow_ups')),
            ExportColumn::make('effective_commissions')
                ->label(__('crm.reports.employee.columns.effective_commissions'))
                ->state(fn (User $record): string => EmployeePerformanceReportQuery::commissionTotalsFor($record->id)['effective']),
            ExportColumn::make('net_paid')
                ->label(__('crm.reports.employee.columns.net_paid'))
                ->state(fn (User $record): string => EmployeePerformanceReportQuery::commissionTotalsFor($record->id)['net_paid']),
            ExportColumn::make('remaining')
                ->label(__('crm.reports.employee.columns.remaining'))
                ->state(fn (User $record): string => EmployeePerformanceReportQuery::commissionTotalsFor($record->id)['remaining']),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $user = auth()->user();

        abort_unless($user instanceof User && CrmReportAccess::canExportEmployeePerformanceReports($user), 403);

        return $query;
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return __('crm.reports.employee.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);
    }
}
