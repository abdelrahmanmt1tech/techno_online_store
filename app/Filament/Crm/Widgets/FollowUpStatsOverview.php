<?php

namespace App\Filament\Crm\Widgets;

use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\OpportunityFollowUp;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FollowUpStatsOverview extends StatsOverviewWidget
{
    use HasTenantFeatureAccess;

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('clients.view') ?? false);
    }

    protected int|array|null $columns = 5;

    protected function getStats(): array
    {
        $base = OpportunityFollowUp::query();

        $pending = (clone $base)->whereNull('completed_at')->count();
        $upcoming = (clone $base)
            ->whereNull('completed_at')
            ->whereBetween('scheduled_at', [now(), now()->addDays(7)])
            ->count();
        $overdue = (clone $base)->overdue()->count();
        $completedToday = (clone $base)
            ->whereDate('completed_at', today())
            ->count();
        $myPending = (clone $base)
            ->whereNull('completed_at')
            ->where('assigned_to', Auth::id())
            ->count();
        $rescheduled = (clone $base)->whereNotNull('parent_follow_up_id')->count();

        return [
            /* Stat::make(__('crm.widgets.follow_ups_pending'), $pending)
                ->description(__('crm.widgets.follow_ups_pending_desc'))
                ->color('info'),*/
            Stat::make(__('crm.widgets.follow_ups_upcoming'), $upcoming)
                ->description(__('crm.widgets.follow_ups_upcoming_desc'))
                ->color('warning'),
            Stat::make(__('crm.widgets.overdue_follow_ups'), $overdue)
                ->color('danger'),
            Stat::make(__('crm.widgets.follow_ups_completed_today'), $completedToday)
                ->color('success'),
            Stat::make(__('crm.widgets.my_pending_follow_ups'), $myPending)
                ->color('primary'),
            Stat::make(__('crm.widgets.rescheduled_follow_ups'), $rescheduled)
                ->description(__('crm.widgets.rescheduled_follow_ups_desc'))
                ->color('gray'),
        ];
    }
}
