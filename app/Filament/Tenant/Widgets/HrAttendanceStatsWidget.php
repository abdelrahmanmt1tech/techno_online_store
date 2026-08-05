<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HrAttendanceStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 30;

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null
            && tenant_module_enabled(\App\Support\Modules\TenantModule::Hr)
            && $user->can('dashboard.view')
            && $user->can('dashboard.hr.view');
    }

    protected function getStats(): array
    {
        $hr = app(DashboardMetricsService::class)->hrAttendanceStats();

        return [
            Stat::make(__('dashboard.lite.hr_present'), (string) $hr['present'])
                ->description(__('dashboard.lite.hr_present_desc'))
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('dashboard.lite.hr_late'), (string) $hr['late'])
                ->description(__('dashboard.lite.hr_late_desc'))
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            Stat::make(__('dashboard.lite.hr_absent'), (string) $hr['absent'])
                ->description(__('dashboard.lite.hr_absent_desc'))
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),
        ];
    }
}
