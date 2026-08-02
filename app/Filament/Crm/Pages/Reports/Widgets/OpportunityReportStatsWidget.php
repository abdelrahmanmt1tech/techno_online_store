<?php

namespace App\Filament\Crm\Pages\Reports\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

/**
 * Official Filament stats-overview widget for the Opportunity report KPIs.
 * Data is supplied by the page (already branch-scoped + filtered) via the shared `summary` prop,
 * so the widget never re-queries. `#[Reactive]` keeps it in sync when page filters change.
 */
class OpportunityReportStatsWidget extends StatsOverviewWidget
{
    protected int|array|null $columns = 3;

    protected int|string|array $columnSpan = 'full';

    /** @var array<string, mixed> */
    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        $s = $this->summary;

        if ($s === []) {
            return [];
        }

        return [
            Stat::make(__('crm.reports.opportunity.stats.total'), number_format((int) ($s['total'] ?? 0)))
                ->color('gray'),
            Stat::make(__('crm.reports.opportunity.stats.open'), number_format((int) ($s['open'] ?? 0)))
                ->color('info'),
            Stat::make(__('crm.reports.opportunity.stats.won'), number_format((int) ($s['won'] ?? 0)))
                ->color('success'),
            Stat::make(__('crm.reports.opportunity.stats.lost'), number_format((int) ($s['lost'] ?? 0)))
                ->color('danger'),
            Stat::make(__('crm.reports.opportunity.stats.amount_total'), number_format((float) ($s['amount_total'] ?? 0), 2))
                ->color('primary'),
            Stat::make(__('crm.reports.opportunity.stats.agreed_amount_total'), number_format((float) ($s['agreed_amount_total'] ?? 0), 2))
                ->color('primary'),
            Stat::make(__('crm.reports.opportunity.stats.close_rate'), number_format((float) ($s['close_rate'] ?? 0), 2).'%')
                ->color('info'),
            Stat::make(__('crm.reports.opportunity.stats.success_rate'), number_format((float) ($s['success_rate'] ?? 0), 2).'%')
                ->color('success'),
            Stat::make(
                __('crm.reports.opportunity.stats.average_close_days'),
                ($s['average_close_days'] ?? null) !== null ? number_format((float) $s['average_close_days'], 1) : '-',
            )->color('warning'),
        ];
    }
}
