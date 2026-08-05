<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class LowStockWidget extends Widget
{
    protected static ?int $sort = 60;

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected string $view = 'filament.tenant.widgets.low-stock';

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null
            && tenant_module_enabled(\App\Support\Modules\TenantModule::Pos)
            && $user->can('dashboard.view')
            && $user->can('dashboard.inventory.view');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'items' => app(DashboardMetricsService::class)->lowStockItems(5),
        ];
    }
}
