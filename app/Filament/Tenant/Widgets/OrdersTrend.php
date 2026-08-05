<?php

namespace App\Filament\Tenant\Widgets;

use App\Models\Tenant\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OrdersTrend extends ChartWidget
{
    protected static ?int $sort = 81;

    protected int|string|array $columnSpan = 'full';

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
        return __('dashboard.widget.orders_trend');
    }

    protected function getData(): array
    {
        $data = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->pluck('count', 'date')
            ->toArray();

        $dates = [];
        $counts = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dates[] = now()->subDays($i)->format('d/m');
            $counts[] = $data[$date] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widget.orders_per_day'),
                    'data' => $counts,
                    'fill' => true,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgba(16, 185, 129, 1)',
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
