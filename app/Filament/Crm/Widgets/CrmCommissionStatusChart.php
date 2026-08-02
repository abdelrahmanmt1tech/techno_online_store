<?php

namespace App\Filament\Crm\Widgets;

use App\Enums\Crm\CommissionStatus;
use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CrmCommissionStatusChart extends ChartWidget
{
    use HasTenantFeatureAccess;

    protected static ?string $chartId = 'crmCommissionStatusChart';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('crm_commissions.view_any') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.chart_commissions_by_status');
    }

    protected function getData(): array
    {
        $user = Auth::user();

        if (! $user instanceof TenantUser) {
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
            'datasets' => [[
                'label' => __('crm.widgets.chart_commissions_by_status'),
                'data' => $series,
                'backgroundColor' => $colors,
                'borderColor' => $colors,
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
                'label' => __('crm.widgets.chart_commissions_by_status'),
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
