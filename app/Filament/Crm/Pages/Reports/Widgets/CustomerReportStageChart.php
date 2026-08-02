<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

class CustomerReportStageChart extends AbstractCrmReportBarChart
{
    protected static ?string $chartId = 'customerReportStageChart';

    public function getHeading(): ?string
    {
        return __('crm.reports.customer.charts.by_stage');
    }

    protected function dataKey(): string
    {
        return 'by_stage';
    }
}
