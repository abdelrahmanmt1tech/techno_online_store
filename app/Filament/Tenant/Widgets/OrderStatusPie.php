<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenant\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OrderStatusPie extends ChartWidget
{
    protected static ?int $sort = 82;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null
            && tenant_module_enabled(\App\Support\Modules\TenantModule::Store)
            && $user->can('dashboard.view')
            && $user->can('dashboard.store.view');
    }

    public function getHeading(): ?string
    {
        return __('dashboard.widget.order_status');
    }

    protected function getData(): array
    {
        $data = Order::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusColors = [
            'pending' => ['bg' => 'rgba(251, 191, 36, 0.8)', 'border' => 'rgba(251, 191, 36, 1)'],
            'confirmed' => ['bg' => 'rgba(59, 130, 246, 0.8)', 'border' => 'rgba(59, 130, 246, 1)'],
            'processing' => ['bg' => 'rgba(168, 85, 247, 0.8)', 'border' => 'rgba(168, 85, 247, 1)'],
            'shipped' => ['bg' => 'rgba(14, 165, 233, 0.8)', 'border' => 'rgba(14, 165, 233, 1)'],
            'delivered' => ['bg' => 'rgba(34, 197, 94, 0.8)', 'border' => 'rgba(34, 197, 94, 1)'],
            'cancelled' => ['bg' => 'rgba(239, 68, 68, 0.8)', 'border' => 'rgba(239, 68, 68, 1)'],
            'returned' => ['bg' => 'rgba(156, 163, 175, 0.8)', 'border' => 'rgba(156, 163, 175, 1)'],
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
                    'label' => __('dashboard.widget.orders_count'),
                    'data' => array_values($data),
                    'backgroundColor' => $bgColors,
                    'borderColor' => $borderColors,
                ],
            ],
            'labels' => array_map(fn ($s) => __('dashboard.'.$s), array_keys($data)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
