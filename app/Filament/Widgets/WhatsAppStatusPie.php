<?php

namespace App\Filament\Widgets;

use App\Models\WhatsAppNumberRegistry;
use Filament\Widgets\ChartWidget;

class WhatsAppStatusPie extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): ?string
    {
        return __('dashboard.widget.whatsapp_status');
    }

    protected function getData(): array
    {
        $data = WhatsAppNumberRegistry::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusColors = [
            'active' => ['bg' => 'rgba(34, 197, 94, 0.8)', 'border' => 'rgba(34, 197, 94, 1)'],
            'disabled' => ['bg' => 'rgba(156, 163, 175, 0.8)', 'border' => 'rgba(156, 163, 175, 1)'],
            'reconnect_required' => ['bg' => 'rgba(251, 191, 36, 0.8)', 'border' => 'rgba(251, 191, 36, 1)'],
            'failed' => ['bg' => 'rgba(239, 68, 68, 0.8)', 'border' => 'rgba(239, 68, 68, 1)'],
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
                    'label' => __('dashboard.widget.whatsapp_numbers'),
                    'data' => array_values($data),
                    'backgroundColor' => $bgColors,
                    'borderColor' => $borderColors,
                ],
            ],
            'labels' => array_map(fn ($s) => __('dashboard.widget.status_'.$s), array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
