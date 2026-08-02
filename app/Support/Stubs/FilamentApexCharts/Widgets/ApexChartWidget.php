<?php

namespace Leandrocfe\FilamentApexCharts\Widgets;

use Filament\Widgets\ChartWidget;

/**
 * Temporary stub: leandrocfe/filament-apex-charts requires Illuminate ^11|^12,
 * while techno_online_store runs Laravel 13. Charts fall back to Filament ChartWidget
 * until we install a compatible package or rewrite chart widgets.
 */
abstract class ApexChartWidget extends ChartWidget
{
    protected static ?string $chartId = null;

    protected function getType(): string
    {
        return 'bar';
    }
}
