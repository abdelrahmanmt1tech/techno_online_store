<?php

namespace App\Services\Crm\Reports;

use App\Models\Tenant\Client;
use App\Models\Tenant\LeadSource;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Builder;

final class SourceReportQuery
{
    /**
     * @return Builder<LeadSource>
     */
    public static function tableQuery(User $user, CrmReportFilters $filters): Builder
    {
        $query = LeadSource::query()->select('lead_sources.*');

        self::applyAggregates($query, $user, $filters);

        return $query->orderBy('lead_sources.name');
    }

    /**
     * @param  Builder<LeadSource>  $query
     */
    protected static function applyAggregates(Builder $query, User $user, CrmReportFilters $filters): void
    {
        $query->withCount([
            'clients as clients_count' => fn (Builder $q) => self::applyClientSideFilters($q, $user, $filters),
            'opportunities as opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters),
            'opportunities as open_opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)->open(),
            'opportunities as won_opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)->won(),
            'opportunities as lost_opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)->lost(),
        ])->withSum(
            ['opportunities as amount_total' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)],
            'amount',
        )->withSum(
            ['opportunities as agreed_amount_total' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)],
            'agreed_amount',
        );
    }

    /**
     * @return array{
     *     sources_count: int,
     *     clients_total: int,
     *     opportunities_total: int,
     *     open_total: int,
     *     won_total: int,
     *     lost_total: int,
     *     amount_total: float,
     *     agreed_amount_total: float,
     *     conversion_rate: float,
     *     average_opportunity_amount: float,
     * }
     */
    public static function summary(User $user, CrmReportFilters $filters): array
    {
        $opportunityBase = Opportunity::query();
        CrmReportScope::applyOpportunityFilters($opportunityBase, $user, self::opportunityFilters($filters));

        if ($filters->leadSourceId !== null) {
            $opportunityBase->whereHas('client', fn (Builder $q) => $q->where('lead_source_id', $filters->leadSourceId));
        }

        if ($filters->salesRepId !== null) {
            $opportunityBase->where('assigned_to', $filters->salesRepId);
        }

        if ($filters->clientStage !== null) {
            $opportunityBase->whereHas('client', fn (Builder $q) => $q->where('stage', $filters->clientStage));
        }

        $opportunitiesTotal = (clone $opportunityBase)->count();
        $openTotal = (clone $opportunityBase)->open()->count();
        $wonTotal = (clone $opportunityBase)->won()->count();
        $lostTotal = (clone $opportunityBase)->lost()->count();
        $amountTotal = (float) (clone $opportunityBase)->sum('amount');
        $agreedTotal = (float) (clone $opportunityBase)->sum('agreed_amount');

        $closedTotal = $wonTotal + $lostTotal;
        $conversionRate = $closedTotal > 0 ? round(($wonTotal / $closedTotal) * 100, 2) : 0.0;
        $averageAmount = $opportunitiesTotal > 0 ? round($amountTotal / $opportunitiesTotal, 2) : 0.0;

        $clientBase = LeadSource::query();
        $sourcesCount = (clone $clientBase)->count();

        $clientsTotal = (clone $clientBase)
            ->withCount(['clients as clients_count' => fn (Builder $q) => self::applyClientSideFilters($q, $user, $filters)])
            ->get()
            ->sum('clients_count');

        return [
            'sources_count' => $sourcesCount,
            'clients_total' => (int) $clientsTotal,
            'opportunities_total' => $opportunitiesTotal,
            'open_total' => $openTotal,
            'won_total' => $wonTotal,
            'lost_total' => $lostTotal,
            'amount_total' => $amountTotal,
            'agreed_amount_total' => $agreedTotal,
            'conversion_rate' => $conversionRate,
            'average_opportunity_amount' => $averageAmount,
        ];
    }

    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    protected static function applyClientSideFilters(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        $clientFilters = new CrmReportFilters(
            from: $filters->from,
            to: $filters->to,
            dateBasis: $filters->dateBasis === 'opportunities.created_at' ? 'created_at' : $filters->dateBasis,
            branchId: $filters->branchId,
            salesRepId: $filters->salesRepId,
            clientStage: $filters->clientStage,
        );

        CrmReportScope::applyClientFilters($query, $user, $clientFilters);

        return $query;
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    protected static function applyOpportunitySideFilters(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        return CrmReportScope::applyOpportunityFilters($query, $user, self::opportunityFilters($filters));
    }

    protected static function opportunityFilters(CrmReportFilters $filters): CrmReportFilters
    {
        $dateBasis = $filters->dateBasis === 'clients.created_at' ? 'created_at' : $filters->dateBasis;

        return new CrmReportFilters(
            from: $filters->from,
            to: $filters->to,
            dateBasis: in_array($dateBasis, ['created_at', 'closed_at'], true) ? $dateBasis : 'created_at',
            branchId: $filters->branchId,
            salesRepId: $filters->salesRepId,
            clientStage: $filters->clientStage,
            campaignId: $filters->campaignId,
            opportunityStageId: $filters->opportunityStageId,
        );
    }
}
