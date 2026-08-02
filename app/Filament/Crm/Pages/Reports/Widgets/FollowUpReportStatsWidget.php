<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\StatsOverviewWidget\Stat;

class FollowUpReportStatsWidget extends AbstractCrmReportStatsWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(__('crm.reports.followup.stats.total'), $this->intValue('total'))->color('gray'),
            Stat::make(__('crm.reports.followup.stats.scheduled'), $this->intValue('scheduled'))->color('info'),
            Stat::make(__('crm.reports.followup.stats.completed'), $this->intValue('completed'))->color('success'),
            Stat::make(__('crm.reports.followup.stats.overdue'), $this->intValue('overdue'))->color('danger'),
            Stat::make(__('crm.reports.followup.stats.completed_on_time'), $this->intValue('completed_on_time'))->color('success'),
            Stat::make(__('crm.reports.followup.stats.average_per_opportunity'), (string) ($this->summary['average_per_opportunity'] ?? '0'))->color('info'),
            Stat::make(__('crm.reports.followup.stats.opportunities_without_follow_up'), $this->intValue('opportunities_without_follow_up'))->color('warning'),
            Stat::make(__('crm.reports.followup.stats.clients_without_follow_up'), $this->intValue('clients_without_follow_up'))->color('warning'),
        ];
    }
}
