<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Attributes\Reactive;

/**
 * Base for CRM report distribution charts. Reads a distribution map (label => count)
 * from the shared `summary` array under a subclass-defined key. `#[Reactive]`
 * keeps it in sync with page filters.
 */
abstract class AbstractCrmReportBarChart extends ChartWidget
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

    protected function getData(): array
    {
        $data = is_array($this->summary[$this->dataKey()] ?? null) ? $this->summary[$this->dataKey()] : [];

        if ($data === []) {
            return [
                'datasets' => [[
                    'label' => $this->seriesName(),
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
                'label' => $this->seriesName(),
                'data' => array_values($data),
                'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                'borderColor' => 'rgba(59, 130, 246, 1)',
                'borderWidth' => 1,
            ]],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
