<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

class CampaignReportStatusChart extends AbstractCrmReportBarChart
{
    protected static ?string $chartId = 'campaignReportStatusChart';

    public function getHeading(): ?string
    {
        return __('crm.reports.campaign.charts.by_status');
    }

    protected function dataKey(): string
    {
        return 'by_status';
    }
}
