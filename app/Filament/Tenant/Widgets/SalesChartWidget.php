<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SalesChartWidget extends ChartWidget
{
    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('dashboard.lite.sales_chart');
    }

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null
            && tenant_module_enabled(\App\Support\Modules\TenantModule::Pos)
            && $user->can('dashboard.view')
            && $user->can('dashboard.sales.view');
    }

    protected function getData(): array
    {
        $chart = app(DashboardMetricsService::class)->salesChart(7);

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.lite.sales_per_day'),
                    'data' => $chart['values'],
                    'fill' => true,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'tension' => 0.35,
                ],
            ],
            'labels' => $chart['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
