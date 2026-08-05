<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class AttendanceTodayWidget extends Widget
{
    protected static ?int $sort = 70;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected string $view = 'filament.tenant.widgets.attendance-today';

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null
            && tenant_module_enabled(\App\Support\Modules\TenantModule::Hr)
            && $user->can('dashboard.view')
            && $user->can('dashboard.hr.view');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'records' => app(DashboardMetricsService::class)->attendanceToday(5),
        ];
    }
}
