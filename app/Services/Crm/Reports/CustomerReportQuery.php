<?php

namespace App\Services\Crm\Reports;

use App\Enums\Crm\ClientStage;
use App\Models\Tenant\Client;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\TenantUser;
use App\Services\Crm\UserCrmReportQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CustomerReportQuery
{
    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    public static function tableQuery(TenantUser $user, CrmReportFilters $filters): Builder
    {
        $branchIds = CrmReportScope::branchIdsForFilters($user, $filters);

        $query = Client::query()
            ->select('clients.*')
            ->with(['leadSource', 'salesRep'])
            ->withCount([
                'opportunities as opportunities_count' => fn (Builder $q) => self::scopedOpportunityCount($q, $branchIds),
                'opportunities as won_opportunities_count' => fn (Builder $q) => self::scopedOpportunityCount($q, $branchIds)->won(),
            ])
            ->withSum(
                ['opportunities as opportunities_agreed_amount_total' => fn (Builder $q) => self::scopedOpportunityCount($q, $branchIds)],
                'agreed_amount',
            )
            ->withMax(
                ['opportunityFollowUps as last_follow_up_at' => function (Builder $q) use ($branchIds): void {
                    $q->whereNotNull('scheduled_at');
                    self::scopeFollowUpsToBranches($q, $branchIds);
                }],
                'scheduled_at',
            );

        CrmReportScope::applyClientFilters($query, $user, $filters);

        return $query->orderByDesc('clients.created_at');
    }

    /**
     * @return array{
     *     total_clients: int,
     *     new_clients: int,
     *     with_opportunities: int,
     *     without_opportunities: int,
     *     with_won_opportunities: int,
     *     conversion_rate: float,
     *     average_opportunities: float,
     *     by_stage: array<string, int>,
     *     by_source: array<string, int>,
     *     by_employee: array<string, int>,
     * }
     */
    public static function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        $base = Client::query();
        CrmReportScope::applyClientFilters($base, $user, $filters);

        $total = (clone $base)->count();

        $newClients = (clone $base)
            ->when(filled($filters->from) || filled($filters->to), function (Builder $query) use ($filters): void {
                UserCrmReportQuery::applyDateRange(
                    $query,
                    $filters->from,
                    $filters->to,
                    in_array($filters->dateBasis, ['created_at', 'updated_at'], true) ? $filters->dateBasis : 'created_at',
                );
            })
            ->count();

        $branchIds = CrmReportScope::branchIdsForFilters($user, $filters);

        $withOpportunities = (clone $base)
            ->whereHas('opportunities', fn (Builder $q) => self::scopedOpportunityCount($q, $branchIds))
            ->count();

        $withoutOpportunities = max(0, $total - $withOpportunities);

        $withWon = (clone $base)
            ->whereHas('opportunities', fn (Builder $q) => self::scopedOpportunityCount($q, $branchIds)->won())
            ->count();

        $conversionRate = $withOpportunities > 0
            ? round(($withWon / $withOpportunities) * 100, 2)
            : 0.0;

        $clientIds = (clone $base)->pluck('clients.id');
        $opportunityCount = $clientIds->isEmpty()
            ? 0
            : Opportunity::query()
                ->whereIn('client_id', $clientIds)
                ->when(is_array($branchIds) && $branchIds !== [], fn (Builder $q) => $q->whereIn('branch_id', $branchIds))
                ->when($branchIds === [], fn (Builder $q) => $q->whereRaw('0 = 1'))
                ->count();

        $averageOpportunities = $total > 0 ? round($opportunityCount / $total, 2) : 0.0;

        return [
            'total_clients' => $total,
            'new_clients' => $newClients,
            'with_opportunities' => $withOpportunities,
            'without_opportunities' => $withoutOpportunities,
            'with_won_opportunities' => $withWon,
            'conversion_rate' => $conversionRate,
            'average_opportunities' => $averageOpportunities,
            'by_stage' => self::groupClientsBy($base, 'stage'),
            'by_source' => self::groupClientsByLeadSource($base),
            'by_employee' => self::groupClientsByEmployee($base),
        ];
    }

    /**
     * @param  Builder<Client>  $base
     * @return array<string, int>
     */
    protected static function groupClientsBy(Builder $base, string $column): array
    {
        /** @var Collection<int, object{label: string, total: int}> $rows */
        $rows = (clone $base)
            ->select("{$column} as label", DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $label = $row->label instanceof ClientStage
                ? $row->label->label()
                : (string) ($row->label ?? __('crm.reports.common.not_specified'));
            $result[$label] = (int) $row->total;
        }

        return $result;
    }

    /**
     * @param  Builder<Client>  $base
     * @return array<string, int>
     */
    protected static function groupClientsByLeadSource(Builder $base): array
    {
        /** @var Collection<int, object{name: string|null, total: int}> $rows */
        $rows = (clone $base)
            ->leftJoin('lead_sources', 'lead_sources.id', '=', 'clients.lead_source_id')
            ->select('lead_sources.name', DB::raw('COUNT(clients.id) as total'))
            ->groupBy('lead_sources.id', 'lead_sources.name')
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
     * @param  Builder<Client>  $base
     * @return array<string, int>
     */
    protected static function groupClientsByEmployee(Builder $base): array
    {
        /** @var Collection<int, object{name: string|null, total: int}> $rows */
        $rows = (clone $base)
            ->leftJoin('users', 'users.id', '=', 'clients.sales_rep_id')
            ->select('users.name', DB::raw('COUNT(clients.id) as total'))
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

    /**
     * @param  Builder<Opportunity>  $query
     * @param  list<int>|null  $branchIds  null = unrestricted, [] = deny, list = restrict
     * @return Builder<Opportunity>
     */
    protected static function scopedOpportunityCount(Builder $query, ?array $branchIds): Builder
    {
        if ($branchIds === null) {
            return $query;
        }

        if ($branchIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Constrain a follow-up sub-query to opportunities within the effective branch scope.
     *
     * @param  Builder<OpportunityFollowUp>  $query
     * @param  list<int>|null  $branchIds  null = unrestricted, [] = deny, list = restrict
     */
    protected static function scopeFollowUpsToBranches(Builder $query, ?array $branchIds): void
    {
        if ($branchIds === null) {
            return;
        }

        if ($branchIds === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereHas('opportunity', fn (Builder $opportunity) => $opportunity->whereIn('branch_id', $branchIds));
    }
}
