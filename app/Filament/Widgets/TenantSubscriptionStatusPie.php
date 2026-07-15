<?php

namespace App\Filament\Widgets;

use App\Models\TenantSubscription;
use Filament\Widgets\ChartWidget;

class TenantSubscriptionStatusPie extends ChartWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('dashboard.widget.subscription_status');
    }

    protected function getData(): array
    {
        $data = TenantSubscription::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusColors = [
            'active' => ['bg' => 'rgba(34, 197, 94, 0.8)', 'border' => 'rgba(34, 197, 94, 1)'],
            'expired' => ['bg' => 'rgba(239, 68, 68, 0.8)', 'border' => 'rgba(239, 68, 68, 1)'],
            'cancelled' => ['bg' => 'rgba(156, 163, 175, 0.8)', 'border' => 'rgba(156, 163, 175, 1)'],
            'pending' => ['bg' => 'rgba(251, 191, 36, 0.8)', 'border' => 'rgba(251, 191, 36, 1)'],
        ];

        $bgColors = [];
        $borderColors = [];

        foreach (array_keys($data) as $status) {
            $bgColors[] = $statusColors[$status]['bg'] ?? 'rgba(156, 163, 175, 0.8)';
            $borderColors[] = $statusColors[$status]['border'] ?? 'rgba(156, 163, 175, 1)';
        }

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widget.subscriptions'),
                    'data' => array_values($data),
                    'backgroundColor' => $bgColors,
                    'borderColor' => $borderColors,
                ],
            ],
            'labels' => array_map(fn ($s) => __('dashboard.widget.subscription_'.$s), array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
