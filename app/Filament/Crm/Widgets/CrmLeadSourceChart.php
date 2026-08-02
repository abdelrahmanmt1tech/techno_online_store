<?php

namespace App\Filament\Crm\Widgets;

use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\Client;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CrmLeadSourceChart extends ApexChartWidget
{
    use HasTenantFeatureAccess;

    protected static ?string $chartId = 'crmLeadSourceChart';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('clients.view') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.chart_clients_by_source');
    }

    protected function getOptions(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->emptyDonut();
        }

        $query = Client::query()->with('leadSource');

        if (! CrmBranchVisibility::canViewAllBranches($user)) {
            $query->whereHas('opportunities', fn (Builder $q) => CrmBranchVisibility::applyOpportunityScope($q, $user));
        }

        $rows = $query->get()->groupBy('lead_source_id');

        $labels = [];
        $series = [];

        foreach ($rows as $group) {
            $source = $group->first()?->leadSource;
            $name = $source?->name;
            $labels[] = is_array($name)
                ? ($name[app()->getLocale()] ?? reset($name) ?: __('dashboard.widgets.not_specified'))
                : ($name ?? __('dashboard.widgets.not_specified'));
            $series[] = $group->count();
        }

        if ($series === []) {
            return $this->emptyDonut();
        }

        return [
            'chart' => ['type' => 'donut', 'height' => 300],
            'series' => $series,
            'labels' => $labels,
            'legend' => ['position' => 'bottom'],
            'plotOptions' => ['pie' => ['donut' => ['size' => '60%']]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyDonut(): array
    {
        return [
            'chart' => ['type' => 'donut', 'height' => 300],
            'series' => [0],
            'labels' => [__('crm.widgets.no_data')],
            'colors' => ['#d1d5db'],
        ];
    }
}
