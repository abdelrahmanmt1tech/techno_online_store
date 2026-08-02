<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\Reactive;

/**
 * Official filament-apex-charts widget for the "opportunities by stage" distribution.
 * Reads the shared `summary` prop supplied by the page (already branch-scoped). `#[Reactive]`
 * keeps it in sync when page filters change.
 */
class OpportunityReportStageChart extends ApexChartWidget
{
    protected static ?string $chartId = 'opportunityReportStageChart';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /** @var array<string, mixed> */
    #[Reactive]
    public array $summary = [];

    public function getHeading(): ?string
    {
        return __('crm.reports.opportunity.charts.by_stage');
    }

    protected function getOptions(): array
    {
        $byStage = is_array($this->summary['by_stage'] ?? null) ? $this->summary['by_stage'] : [];

        if ($byStage === []) {
            return [
                'chart' => ['type' => 'bar', 'height' => 300],
                'series' => [['name' => __('crm.reports.opportunity.stats.total'), 'data' => [0]]],
                'xaxis' => ['categories' => [__('crm.widgets.no_data')]],
            ];
        }

        return [
            'chart' => ['type' => 'bar', 'height' => 320, 'toolbar' => ['show' => false]],
            'series' => [[
                'name' => __('crm.reports.opportunity.stats.total'),
                'data' => array_values($byStage),
            ]],
            'xaxis' => ['categories' => array_keys($byStage)],
            'colors' => ['#3b82f6'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => true],
        ];
    }
}
