<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PosInventoryStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 20;

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        if ($user === null || ! $user->can('dashboard.view') || ! tenant_module_enabled(\App\Support\Modules\TenantModule::Pos)) {
            return false;
        }

        return $user->can('dashboard.pos.view') || $user->can('dashboard.inventory.view');
    }

    protected function getStats(): array
    {
        $user = Auth::guard('tenant')->user();
        $metrics = app(DashboardMetricsService::class);
        $stats = [];

        if ($user?->can('dashboard.pos.view')) {
            $pos = $metrics->posStats();
            $stats[] = Stat::make(__('dashboard.lite.pos_today_count'), (string) $pos['pos_today_count'])
                ->description(__('dashboard.lite.pos_today_count_desc'))
                ->descriptionIcon('heroicon-o-calculator')
                ->color('primary');
            $stats[] = Stat::make(__('dashboard.lite.pos_today_collected'), $pos['pos_today_collected'])
                ->description(__('dashboard.lite.pos_today_collected_desc'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success');
            $stats[] = Stat::make(__('dashboard.lite.open_shifts'), (string) $pos['open_shifts'])
                ->description(__('dashboard.lite.open_shifts_desc'))
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color($pos['open_shifts'] > 0 ? 'warning' : 'gray');
        }

        if ($user?->can('dashboard.inventory.view')) {
            $inventory = $metrics->inventoryStats();
            $stats[] = Stat::make(__('dashboard.lite.low_stock'), (string) $inventory['low_stock_count'])
                ->description(__('dashboard.lite.low_stock_desc'))
                ->descriptionIcon('heroicon-o-archive-box')
                ->color($inventory['low_stock_count'] > 0 ? 'warning' : 'success');
            $stats[] = Stat::make(__('dashboard.lite.out_of_stock'), (string) $inventory['out_of_stock_count'])
                ->description(__('dashboard.lite.out_of_stock_desc'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color($inventory['out_of_stock_count'] > 0 ? 'danger' : 'success');
        }

        return $stats;
    }
}
