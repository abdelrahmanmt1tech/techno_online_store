<?php

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\DashboardMetricsService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class LatestSalesWidget extends Widget
{
    protected static ?int $sort = 50;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.tenant.widgets.latest-sales';

    public static function canView(): bool
    {
        $user = Auth::guard('tenant')->user();

        return $user !== null
            && tenant_module_enabled(\App\Support\Modules\TenantModule::Pos)
            && $user->can('dashboard.view')
            && $user->can('dashboard.sales.view');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = Auth::guard('tenant')->user();
        $canOpen = $user?->can('erp.sales.view') ?? false;

        return [
            'sales' => app(DashboardMetricsService::class)->latestSales(5),
            'canOpen' => $canOpen,
        ];
    }
}
