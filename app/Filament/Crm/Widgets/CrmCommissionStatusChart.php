<?php

namespace App\Filament\Crm\Widgets;

use App\Enums\Crm\CommissionStatus;
use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use Illuminate\Support\Facades\Auth;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class CrmCommissionStatusChart extends ApexChartWidget
{
    use HasTenantFeatureAccess;

    protected static ?string $chartId = 'crmCommissionStatusChart';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('crm_commissions.view_any') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.chart_commissions_by_status');
    }

    protected function getOptions(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return $this->emptyDonut();
        }

        $counts = OpportunityCommission::query()
            ->visibleToUser($user)
            ->whereNot('status', CommissionStatus::DRAFT)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $series = [];
        $colors = [];

        foreach ($counts as $status => $total) {
            $enum = CommissionStatus::tryFrom((string) $status);
            $labels[] = $enum?->label() ?? (string) $status;
            $series[] = (int) $total;
            $colors[] = match ($enum) {
                CommissionStatus::PENDING => '#f59e0b',
                CommissionStatus::APPROVED => '#06b6d4',
                CommissionStatus::PARTIALLY_PAID => '#8b5cf6',
                CommissionStatus::PAID => '#22c55e',
                CommissionStatus::REJECTED, CommissionStatus::CANCELLED => '#ef4444',
                default => '#6b7280',
            };
        }

        if ($series === []) {
            return $this->emptyDonut();
        }

        return [
            'chart' => ['type' => 'donut', 'height' => 300],
            'series' => $series,
            'labels' => $labels,
            'colors' => $colors,
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
