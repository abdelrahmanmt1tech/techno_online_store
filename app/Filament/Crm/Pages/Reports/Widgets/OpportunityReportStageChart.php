<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

/**
 * Distribution of opportunities by stage from the page summary payload.
 */
class OpportunityReportStageChart extends ChartWidget
{
    protected static ?string $chartId = 'opportunityReportStageChart';

    protected int|string|array $columnSpan = 'full';

    /** @var array<string, mixed> */
    #[Reactive]
    public array $summary = [];

    public function getHeading(): ?string
    {
        return __('crm.reports.opportunity.charts.by_stage');
    }

    protected function getData(): array
    {
        $byStage = is_array($this->summary['by_stage'] ?? null) ? $this->summary['by_stage'] : [];

        if ($byStage === []) {
            return [
                'datasets' => [[
                    'label' => __('crm.reports.opportunity.stats.total'),
                    'data' => [0],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                ]],
                'labels' => [__('crm.widgets.no_data')],
            ];
        }

        return [
            'datasets' => [[
                'label' => __('crm.reports.opportunity.stats.total'),
                'data' => array_values($byStage),
                'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                'borderColor' => 'rgba(59, 130, 246, 1)',
                'borderWidth' => 1,
            ]],
            'labels' => array_keys($byStage),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
