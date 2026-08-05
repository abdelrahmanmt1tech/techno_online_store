<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SalesCollectionStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null
            && tenant_module_enabled(\App\Support\Modules\TenantModule::Pos)
            && $user->can('dashboard.view')
            && $user->can('dashboard.sales.view');
    }

    protected function getStats(): array
    {
        $metrics = app(DashboardMetricsService::class);
        $sales = $metrics->salesStats();
        $collection = $metrics->collectionStats();

        return [
            Stat::make(__('dashboard.lite.sales_today'), $sales['sales_today_total'])
                ->description(__('dashboard.lite.sales_today_desc'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make(__('dashboard.lite.sales_month'), $sales['sales_month_total'])
                ->description(__('dashboard.lite.sales_month_desc'))
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('primary'),
            Stat::make(__('dashboard.lite.sales_today_count'), (string) $sales['sales_today_count'])
                ->description(__('dashboard.lite.sales_today_count_desc'))
                ->descriptionIcon('heroicon-o-shopping-cart')
                ->color('info'),
            Stat::make(__('dashboard.lite.unpaid_due'), $collection['unpaid_due_total'])
                ->description(__('dashboard.lite.unpaid_due_desc'))
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make(__('dashboard.lite.partially_paid_due'), $collection['partially_paid_due_total'])
                ->description(__('dashboard.lite.partially_paid_due_desc'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
