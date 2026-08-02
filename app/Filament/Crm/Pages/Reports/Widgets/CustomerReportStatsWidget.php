<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerReportStatsWidget extends AbstractCrmReportStatsWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('crm.reports.customer.stats.total_clients'), $this->intValue('total_clients'))->color('gray'),
            Stat::make(__('crm.reports.customer.stats.new_clients'), $this->intValue('new_clients'))->color('info'),
            Stat::make(__('crm.reports.customer.stats.with_opportunities'), $this->intValue('with_opportunities'))->color('primary'),
            Stat::make(__('crm.reports.customer.stats.without_opportunities'), $this->intValue('without_opportunities'))->color('gray'),
            Stat::make(__('crm.reports.customer.stats.with_won_opportunities'), $this->intValue('with_won_opportunities'))->color('success'),
            Stat::make(__('crm.reports.customer.stats.conversion_rate'), $this->percent('conversion_rate'))->color('success'),
            Stat::make(__('crm.reports.customer.stats.average_opportunities'), number_format((float) ($this->summary['average_opportunities'] ?? 0), 2))->color('info'),
        ];
    }
}
