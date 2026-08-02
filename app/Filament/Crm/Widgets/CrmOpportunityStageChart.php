<?php

namespace App\Filament\Crm\Widgets;

use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CrmOpportunityStageChart extends ChartWidget
{
    use HasTenantFeatureAccess;

    protected static ?string $chartId = 'crmOpportunityStageChart';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('clients.view') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.chart_opportunities_by_stage');
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user instanceof TenantUser) {
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
            'datasets' => [[
                'label' => __('crm.widgets.chart_opportunities_by_stage'),
                'data' => $series,
                'backgroundColor' => [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(20, 184, 166, 0.8)',
                ],
                'borderColor' => [
                    'rgba(59, 130, 246, 1)',
                    'rgba(34, 197, 94, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(168, 85, 247, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(20, 184, 166, 1)',
                ],
            ]],
            'labels' => $labels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyDonut(): array
    {
        return [
            'datasets' => [[
                'label' => __('crm.widgets.chart_opportunities_by_stage'),
                'data' => [0],
                'backgroundColor' => ['rgba(209, 213, 219, 0.8)'],
                'borderColor' => ['rgba(209, 213, 219, 1)'],
            ]],
            'labels' => [__('crm.widgets.no_data')],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
