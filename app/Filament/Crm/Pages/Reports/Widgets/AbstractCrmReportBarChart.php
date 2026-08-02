<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Livewire\Attributes\Reactive;

/**
 * Base for CRM report distribution charts (filament-apex-charts bar). Reads a distribution map
 * (label => count) from the shared `summary` array under a subclass-defined key. `#[Reactive]`
 * keeps it in sync with page filters. Each subclass MUST define a unique static $chartId.
 */
abstract class AbstractCrmReportBarChart extends ApexChartWidget
{
    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /** @var array<string, mixed> */
    #[Reactive]
    public array $summary = [];

    abstract protected function dataKey(): string;

    protected function seriesName(): string
    {
        return __('crm.reports.opportunity.stats.total');
    }

    protected function getOptions(): array
    {
        $data = is_array($this->summary[$this->dataKey()] ?? null) ? $this->summary[$this->dataKey()] : [];

        if ($data === []) {
            return [
                'chart' => ['type' => 'bar', 'height' => 300],
                'series' => [['name' => $this->seriesName(), 'data' => [0]]],
                'xaxis' => ['categories' => [__('crm.widgets.no_data')]],
            ];
        }

        return [
            'chart' => ['type' => 'bar', 'height' => 320, 'toolbar' => ['show' => false]],
            'series' => [['name' => $this->seriesName(), 'data' => array_values($data)]],
            'xaxis' => ['categories' => array_keys($data)],
            'colors' => ['#3b82f6'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => true],
        ];
    }
}
