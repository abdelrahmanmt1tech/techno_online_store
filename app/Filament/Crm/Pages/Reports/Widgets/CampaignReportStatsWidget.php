<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use App\Services\Crm\Reports\CrmReportMetrics;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignReportStatsWidget extends AbstractCrmReportStatsWidget
{
    protected function getStats(): array
    {
        $roi = $this->summary['expected_roi'] ?? null;
        $roiDisplay = $roi === CrmReportMetrics::NOT_APPLICABLE
            ? __('crm.reports.common.not_applicable')
            : ($roi !== null ? CrmReportMetrics::displayPercent($roi) : '-');

        return [
            Stat::make(__('crm.reports.campaign.stats.campaigns_count'), $this->intValue('campaigns_count'))->color('gray'),
            Stat::make(__('crm.reports.campaign.stats.opportunities_total'), $this->intValue('opportunities_total'))->color('info'),
            Stat::make(__('crm.reports.campaign.stats.won_total'), $this->intValue('won_total'))->color('success'),
            Stat::make(__('crm.reports.campaign.stats.lost_total'), $this->intValue('lost_total'))->color('danger'),
            Stat::make(__('crm.reports.campaign.stats.amount_total'), (string) ($this->summary['amount_total'] ?? '0'))->color('primary'),
            Stat::make(__('crm.reports.campaign.stats.agreed_amount_total'), (string) ($this->summary['agreed_amount_total'] ?? '0'))->color('primary'),
            Stat::make(__('crm.reports.campaign.stats.conversion_rate'), $this->percent('conversion_rate'))->color('success'),
            Stat::make(__('crm.reports.campaign.stats.expected_roi'), $roiDisplay)->color('warning'),
        ];
    }
}
