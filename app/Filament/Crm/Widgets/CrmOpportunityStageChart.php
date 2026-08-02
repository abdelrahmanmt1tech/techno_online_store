<?php

namespace App\Filament\Crm\Widgets;

use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CrmOpportunityStageChart extends ApexChartWidget
{
    use HasTenantFeatureAccess;

    protected static ?string $chartId = 'crmOpportunityStageChart';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('clients.view') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.chart_opportunities_by_stage');
    }

    protected function getOptions(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->emptyDonut();
        }

        $query = Opportunity::query();
        CrmBranchVisibility::applyOpportunityScope($query, $user);

        $rows = $query->with('opportunityStage')->get()->groupBy('opportunity_stage_id');

        $labels = [];
        $series = [];

        foreach ($rows as $group) {
            $stage = $group->first()?->opportunityStage;
            $labels[] = $stage?->name ?? __('dashboard.widgets.not_specified');
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
