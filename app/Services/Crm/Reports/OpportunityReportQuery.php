<?php

namespace App\Services\Crm\Reports;

use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class OpportunityReportQuery
{
    /**
     * @return Builder<Opportunity>
     */
    public static function tableQuery(User $user, CrmReportFilters $filters): Builder
    {
        $query = Opportunity::query()
            ->select('opportunities.*')
            ->with([
                'client.leadSource',
                'campaign',
                'branch',
                'assignedTo',
                'opportunityStage',
            ])
            ->withCount('opportunityFollowUps')
            ->withMax(['opportunityFollowUps as last_follow_up_at' => fn (Builder $q) => $q->whereNotNull('scheduled_at')], 'scheduled_at');

        CrmReportScope::applyOpportunityFilters($query, $user, $filters);

        return $query->orderByDesc('opportunities.created_at');
    }

    /**
     * @return array{
     *     total: int,
     *     open: int,
     *     won: int,
     *     lost: int,
     *     amount_total: float,
     *     agreed_amount_total: float,
     *     average_amount: float,
     *     average_agreed_amount: float,
     *     close_rate: float,
     *     success_rate: float,
     *     average_close_days: float|null,
     *     by_stage: array<string, int>,
     *     by_employee: array<string, int>,
     * }
     */
    public static function summary(User $user, CrmReportFilters $filters): array
    {
        $base = Opportunity::query();
        CrmReportScope::applyOpportunityFilters($base, $user, $filters);

        $total = (clone $base)->count();
        $open = (clone $base)->open()->count();
        $won = (clone $base)->won()->count();
        $lost = (clone $base)->lost()->count();
        $closed = $won + $lost;

        $amountTotal = (float) (clone $base)->sum('amount');
        $agreedTotal = (float) (clone $base)->sum('agreed_amount');

        $averageAmount = $total > 0 ? round($amountTotal / $total, 2) : 0.0;
        $averageAgreed = $total > 0 ? round($agreedTotal / $total, 2) : 0.0;

        $closeRate = $total > 0 ? round(($closed / $total) * 100, 2) : 0.0;
        $successRate = $closed > 0 ? round(($won / $closed) * 100, 2) : 0.0;

        $averageCloseDays = (clone $base)
            ->closed()
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(DATEDIFF(closed_at, created_at)) as avg_days')
            ->value('avg_days');

        $averageCloseDays = $averageCloseDays !== null ? round((float) $averageCloseDays, 1) : null;

        return [
            'total' => $total,
            'open' => $open,
            'won' => $won,
            'lost' => $lost,
            'amount_total' => $amountTotal,
            'agreed_amount_total' => $agreedTotal,
            'average_amount' => $averageAmount,
            'average_agreed_amount' => $averageAgreed,
            'close_rate' => $closeRate,
            'success_rate' => $successRate,
            'average_close_days' => $averageCloseDays,
            'by_stage' => self::groupByStage($base),
            'by_employee' => self::groupByEmployee($base),
        ];
    }

    /**
     * @param  Builder<Opportunity>  $base
     * @return array<string, int>
     */
    protected static function groupByStage(Builder $base): array
    {
        $rows = (clone $base)
            ->join('opportunity_stages', 'opportunity_stages.id', '=', 'opportunities.opportunity_stage_id')
            ->select('opportunity_stages.name', DB::raw('COUNT(*) as total'))
            ->groupBy('opportunity_stages.id', 'opportunity_stages.name')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $name = is_array($row->name)
                ? ($row->name[app()->getLocale()] ?? reset($row->name) ?: '')
                : (string) ($row->name ?? '');
            $label = $name !== '' ? $name : __('crm.reports.common.not_specified');
            $result[$label] = (int) $row->total;
        }

        return $result;
    }

    /**
     * @param  Builder<Opportunity>  $base
     * @return array<string, int>
     */
    protected static function groupByEmployee(Builder $base): array
    {
        $rows = (clone $base)
            ->leftJoin('users', 'users.id', '=', 'opportunities.assigned_to')
            ->select('users.name', DB::raw('COUNT(*) as total'))
            ->groupBy('users.id', 'users.name')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $label = filled($row->name)
                ? (string) $row->name
                : __('crm.reports.common.not_specified');
            $result[$label] = (int) $row->total;
        }

        return $result;
    }

    public static function closeDurationDays(Opportunity $opportunity): ?int
    {
        if ($opportunity->closed_at === null) {
            return null;
        }

        return $opportunity->created_at?->diffInDays($opportunity->closed_at);
    }
}
