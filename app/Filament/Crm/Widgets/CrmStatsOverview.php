<?php

namespace App\Filament\Crm\Widgets;

use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityFollowUp;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CrmStatsOverview extends StatsOverviewWidget
{
    use HasTenantFeatureAccess;

    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('clients.view') ?? false);
    }

    protected function getStats(): array
    {
        $openCount = Opportunity::query()->open()->count();
        $openAmount = Opportunity::query()->open()->sum('amount');
        $wonCount = Opportunity::query()->won()->count();
        $lostCount = Opportunity::query()->lost()->count();
        $closedTotal = $wonCount + $lostCount;
        $conversion = $closedTotal > 0 ? round(($wonCount / $closedTotal) * 100, 1) : 0;

        $upcoming = OpportunityFollowUp::query()
            ->whereNull('completed_at')
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->count();

        $overdue = OpportunityFollowUp::query()->overdue()->count();

        return [
            Stat::make(__('crm.widgets.open_opportunities'), $openCount)
                ->description(number_format((float) $openAmount, 2).' SAR')
                ->color('info'),
            Stat::make(__('crm.widgets.won_opportunities'), $wonCount)
                ->color('success'),
            Stat::make(__('crm.widgets.lost_opportunities'), $lostCount)
                ->color('danger'),
            Stat::make(__('crm.widgets.upcoming_follow_ups'), $upcoming)
                ->color('warning'),
            Stat::make(__('crm.widgets.overdue_follow_ups'), $overdue)
                ->color('danger'),
            Stat::make(__('crm.widgets.conversion_rate'), $conversion.'%')
                ->description("{$wonCount} / {$closedTotal}")
                ->color('success'),
        ];
    }
}
