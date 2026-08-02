<?php

namespace App\Services\Crm\Reports;

use App\Models\Tenant\Client;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class FollowUpReportQuery
{
    /**
     * @return Builder<OpportunityFollowUp>
     */
    public static function tableQuery(TenantUser $user, CrmReportFilters $filters): Builder
    {
        $query = OpportunityFollowUp::query()
            ->select('opportunity_follow_ups.*')
            ->with([
                'opportunity.client',
                'opportunity.branch',
                'followUpType',
                'followUpStatus',
                'assignedTo',
            ]);

        CrmReportScope::applyFollowUpFilters($query, $user, $filters);

        return $query->orderByDesc('opportunity_follow_ups.scheduled_at');
    }

    /**
     * @return Builder<OpportunityFollowUp>
     */
    protected static function baseQuery(TenantUser $user, CrmReportFilters $filters): Builder
    {
        $query = OpportunityFollowUp::query();
        CrmReportScope::applyFollowUpFilters($query, $user, $filters);

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        $base = self::baseQuery($user, $filters);

        $total = (clone $base)->count();
        $scheduled = (clone $base)->scheduled()->count();
        $completed = (clone $base)->completed()->count();
        $overdue = (clone $base)->overdue()->count();

        $completedOnTime = (clone $base)
            ->completed()
            ->whereNotNull('scheduled_at')
            ->whereColumn('completed_at', '<=', 'scheduled_at')
            ->count();

        $opportunityCount = (clone $base)->distinct('opportunity_id')->count('opportunity_id');
        $averagePerOpportunity = CrmReportMetrics::averagePerItem($total, $opportunityCount);

        $opportunitiesWithoutFollowUp = self::scopedOpportunities($user, $filters)
            ->whereDoesntHave('opportunityFollowUps', fn (Builder $followUp) => self::applyFollowUpSideFilters($followUp, $user, $filters))
            ->count();

        $clientsWithoutFollowUp = self::scopedClients($user, $filters)
            ->whereHas('opportunities', fn (Builder $opportunity) => self::scopedOpportunityExists($opportunity, $user, $filters))
            ->whereDoesntHave(
                'opportunityFollowUps',
                fn (Builder $followUp) => self::applyFollowUpSideFilters($followUp, $user, $filters),
            )
            ->count();

        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'completed' => $completed,
            'overdue' => $overdue,
            'completed_on_time' => $completedOnTime,
            'average_per_opportunity' => $averagePerOpportunity,
            'opportunities_without_follow_up' => $opportunitiesWithoutFollowUp,
            'clients_without_follow_up' => $clientsWithoutFollowUp,
            'by_employee' => self::groupByRelation($base, 'assigned_to', 'users', 'name'),
            'by_type' => self::groupByRelation($base, 'follow_up_type_id', 'follow_up_types', 'name'),
            'by_status' => self::groupByRelation($base, 'follow_up_status_id', 'follow_up_statuses', 'name'),
        ];
    }

    /**
     * @param  Builder<OpportunityFollowUp>  $base
     * @return array<string, int>
     */
    protected static function groupByRelation(
        Builder $base,
        string $foreignKey,
        string $table,
        string $labelColumn,
    ): array {
        $rows = (clone $base)
            ->leftJoin($table, "{$table}.id", '=', "opportunity_follow_ups.{$foreignKey}")
            ->select("{$table}.{$labelColumn}", DB::raw('COUNT(*) as total'))
            ->groupBy("{$table}.id", "{$table}.{$labelColumn}")
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $raw = $row->{$labelColumn} ?? null;
            if (is_array($raw)) {
                $label = $raw[app()->getLocale()] ?? reset($raw) ?: '';
            } else {
                $label = (string) ($raw ?? '');
            }

            if ($label === '') {
                $label = __('crm.reports.common.not_specified');
            }

            $result[$label] = (int) $row->total;
        }

        return $result;
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    protected static function scopedOpportunities(TenantUser $user, CrmReportFilters $filters): Builder
    {
        $query = Opportunity::query();
        CrmReportScope::applyOpportunityFilters($query, $user, self::opportunityFilters($filters));

        if ($filters->opportunityId !== null) {
            $query->whereKey($filters->opportunityId);
        }

        return $query;
    }

    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    protected static function scopedClients(TenantUser $user, CrmReportFilters $filters): Builder
    {
        $query = Client::query();
        CrmReportScope::applyClientFilters($query, $user, self::clientFilters($filters));

        if ($filters->clientId !== null) {
            $query->whereKey($filters->clientId);
        }

        return $query;
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    protected static function scopedOpportunityExists(Builder $query, TenantUser $user, CrmReportFilters $filters): Builder
    {
        return CrmReportScope::applyOpportunityFilters($query, $user, self::opportunityFilters($filters));
    }

    /**
     * @param  Builder<OpportunityFollowUp>  $query
     * @return Builder<OpportunityFollowUp>
     */
    protected static function applyFollowUpSideFilters(Builder $query, TenantUser $user, CrmReportFilters $filters): Builder
    {
        return CrmReportScope::applyFollowUpFilters($query, $user, $filters);
    }

    protected static function opportunityFilters(CrmReportFilters $filters): CrmReportFilters
    {
        return new CrmReportFilters(
            branchId: $filters->branchId,
            salesRepId: $filters->salesRepId,
            leadSourceId: $filters->leadSourceId,
            campaignId: $filters->campaignId,
            clientId: $filters->clientId,
            opportunityId: $filters->opportunityId,
        );
    }

    protected static function clientFilters(CrmReportFilters $filters): CrmReportFilters
    {
        return new CrmReportFilters(
            branchId: $filters->branchId,
            salesRepId: $filters->salesRepId,
            leadSourceId: $filters->leadSourceId,
            clientId: $filters->clientId,
        );
    }
}
