<?php

namespace App\Filament\Crm\Widgets;

use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CrmOpportunitiesTrendChart extends ApexChartWidget
{
    use HasTenantFeatureAccess;

    protected static ?string $chartId = 'crmOpportunitiesTrendChart';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('clients.view') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.chart_opportunities_trend');
    }

    protected function getOptions(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->emptyBar();
        }

        $months = collect(range(5, 0))->map(fn (int $offset): Carbon => now()->startOfMonth()->subMonths($offset));

        $created = [];
        $won = [];
        $labels = [];

        foreach ($months as $month) {
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();
            $labels[] = $month->translatedFormat('M Y');

            $base = Opportunity::query();
            CrmBranchVisibility::applyOpportunityScope($base, $user);
            $created[] = (clone $base)->whereBetween('created_at', [$start, $end])->count();

            $wonBase = Opportunity::query();
            CrmBranchVisibility::applyOpportunityScope($wonBase, $user);
            $won[] = (clone $wonBase)->won()->whereBetween('closed_at', [$start, $end])->count();
        }

        return [
            'chart' => ['type' => 'bar', 'height' => 300, 'stacked' => false],
            'series' => [
                ['name' => __('crm.widgets.trend_created'), 'data' => $created],
                ['name' => __('crm.widgets.trend_won'), 'data' => $won],
            ],
            'xaxis' => ['categories' => $labels],
            'colors' => ['#3b82f6', '#22c55e'],
            'plotOptions' => ['bar' => ['borderRadius' => 4, 'columnWidth' => '55%']],
            'dataLabels' => ['enabled' => false],
            'legend' => ['position' => 'bottom'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyBar(): array
    {
        return [
            'chart' => ['type' => 'bar', 'height' => 300],
            'series' => [['name' => __('crm.widgets.no_data'), 'data' => [0]]],
            'xaxis' => ['categories' => ['-']],
        ];
    }
}
