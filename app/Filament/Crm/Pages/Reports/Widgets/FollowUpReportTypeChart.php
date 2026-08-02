<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

class FollowUpReportTypeChart extends AbstractCrmReportBarChart
{
    protected static ?string $chartId = 'followUpReportTypeChart';

    protected int|string|array $columnSpan = ['default' => 1, 'md' => 1];

    public function getHeading(): ?string
    {
        return __('crm.reports.followup.charts.by_type');
    }

    protected function dataKey(): string
    {
        return 'by_type';
    }
}
