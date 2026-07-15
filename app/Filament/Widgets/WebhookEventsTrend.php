<?php

namespace App\Filament\Widgets;

use App\Models\MessengerWebhookEvent;
use App\Models\WhatsAppWebhookEvent;
use Filament\Widgets\ChartWidget;

class WebhookEventsTrend extends ChartWidget
{
    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return __('dashboard.widget.webhook_events_trend');
    }

    protected function getData(): array
    {
        $whatsappData = WhatsAppWebhookEvent::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->pluck('count', 'date')
            ->toArray();

        $messengerData = MessengerWebhookEvent::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->pluck('count', 'date')
            ->toArray();

        $dates = [];
        $whatsappCounts = [];
        $messengerCounts = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = now()->subDays($i)->format('d/m');
            $whatsappCounts[] = $whatsappData[$date] ?? 0;
            $messengerCounts[] = $messengerData[$date] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widget.whatsapp_events'),
                    'data' => $whatsappCounts,
                    'fill' => true,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'tension' => 0.4,
                ],
                [
                    'label' => __('dashboard.widget.messenger_events'),
                    'data' => $messengerCounts,
                    'fill' => true,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
