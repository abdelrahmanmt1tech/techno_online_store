<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;

class EmployeePerformanceReportStatsWidget extends AbstractCrmReportStatsWidget
{
    protected function getStats(): array
    {
        $avgClose = $this->summary['average_close_days'] ?? null;

        return [
            Stat::make(__('crm.reports.employee.stats.employees_count'), $this->intValue('employees_count'))->color('gray'),
            Stat::make(__('crm.reports.employee.stats.clients_total'), $this->intValue('clients_total'))->color('info'),
            Stat::make(__('crm.reports.employee.stats.opportunities_total'), $this->intValue('opportunities_total'))->color('info'),
            Stat::make(__('crm.reports.employee.stats.conversion_rate'), $this->percent('conversion_rate'))->color('success'),
            Stat::make(__('crm.reports.employee.stats.average_close_days'), $avgClose !== null ? number_format((float) $avgClose, 1) : '-')->color('warning'),
            Stat::make(__('crm.reports.employee.stats.completed_follow_ups'), $this->intValue('completed_follow_ups'))->color('success'),
            Stat::make(__('crm.reports.employee.stats.overdue_follow_ups'), $this->intValue('overdue_follow_ups'))->color('danger'),
            Stat::make(__('crm.reports.employee.stats.effective_commissions_total'), (string) ($this->summary['effective_commissions_total'] ?? '0'))->color('primary'),
        ];
    }
}
