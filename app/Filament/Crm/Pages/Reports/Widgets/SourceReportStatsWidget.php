<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;

class SourceReportStatsWidget extends AbstractCrmReportStatsWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('crm.reports.source.stats.clients_total'), $this->intValue('clients_total'))->color('gray'),
            Stat::make(__('crm.reports.source.stats.opportunities_total'), $this->intValue('opportunities_total'))->color('info'),
            Stat::make(__('crm.reports.source.stats.open_total'), $this->intValue('open_total'))->color('info'),
            Stat::make(__('crm.reports.source.stats.won_total'), $this->intValue('won_total'))->color('success'),
            Stat::make(__('crm.reports.source.stats.lost_total'), $this->intValue('lost_total'))->color('danger'),
            Stat::make(__('crm.reports.source.stats.amount_total'), $this->money('amount_total'))->color('primary'),
            Stat::make(__('crm.reports.source.stats.agreed_amount_total'), $this->money('agreed_amount_total'))->color('primary'),
            Stat::make(__('crm.reports.source.stats.conversion_rate'), $this->percent('conversion_rate'))->color('success'),
        ];
    }
}
