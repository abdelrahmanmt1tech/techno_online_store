<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

class FollowUpReportEmployeeChart extends AbstractCrmReportBarChart
{
    protected static ?string $chartId = 'followUpReportEmployeeChart';

    protected int|string|array $columnSpan = ['default' => 1, 'md' => 1];

    public function getHeading(): ?string
    {
        return __('crm.reports.followup.charts.by_employee');
    }

    protected function dataKey(): string
    {
        return 'by_employee';
    }
}
