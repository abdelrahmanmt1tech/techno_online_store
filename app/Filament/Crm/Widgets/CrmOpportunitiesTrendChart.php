<?php

namespace App\Filament\Crm\Widgets;

use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Support\Crm\CrmBranchVisibility;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class CrmOpportunitiesTrendChart extends ChartWidget
{
    use HasTenantFeatureAccess;

    protected static ?string $chartId = 'crmOpportunitiesTrendChart';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('clients.view') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.chart_opportunities_trend');
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user instanceof TenantUser) {
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
            'datasets' => [
                [
                    'label' => __('crm.widgets.trend_created'),
                    'data' => $created,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => __('crm.widgets.trend_won'),
                    'data' => $won,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.8)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function emptyBar(): array
    {
        return [
            'datasets' => [[
                'label' => __('crm.widgets.no_data'),
                'data' => [0],
                'backgroundColor' => 'rgba(209, 213, 219, 0.8)',
                'borderColor' => 'rgba(209, 213, 219, 1)',
                'borderWidth' => 1,
            ]],
            'labels' => ['-'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
