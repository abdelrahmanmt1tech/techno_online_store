<?php

namespace App\Services\Crm\Reports;

use App\Models\Tenant\Campaign;
use App\Models\Tenant\Client;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\TenantUser;
use App\Services\Crm\UserCrmReportQuery;
use App\Support\Crm\CrmBranchVisibility;
use Illuminate\Database\Eloquent\Builder;

final class CrmReportScope
{
    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public static function applyOpportunityFilters(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        CrmBranchVisibility::applyOpportunityScope($query, $user);

        if ($filters->branchId !== null) {
            $query->where('branch_id', $filters->branchId);
        }

        if ($filters->salesRepId !== null) {
            $query->where('assigned_to', $filters->salesRepId);
        }

        if ($filters->campaignId !== null) {
            $query->where('campaign_id', $filters->campaignId);
        }

        if ($filters->opportunityStageId !== null) {
            $query->where('opportunity_stage_id', $filters->opportunityStageId);
        }

        if ($filters->clientId !== null) {
            $query->where('client_id', $filters->clientId);
        }

        if ($filters->leadSourceId !== null) {
            $query->whereHas('client', fn (Builder $client) => $client->where('lead_source_id', $filters->leadSourceId));
        }

        if ($filters->clientStage !== null) {
            $query->whereHas('client', fn (Builder $client) => $client->where('stage', $filters->clientStage));
        }

        match ($filters->opportunityStatus) {
            'open' => $query->open(),
            'won' => $query->won(),
            'lost' => $query->lost(),
            default => null,
        };

        if ($filters->amountFrom !== null) {
            $query->where('amount', '>=', $filters->amountFrom);
        }

        if ($filters->amountTo !== null) {
            $query->where('amount', '<=', $filters->amountTo);
        }

        $dateColumn = in_array($filters->dateBasis, ['created_at', 'closed_at'], true)
            ? $filters->dateBasis
            : 'created_at';

        UserCrmReportQuery::applyDateRange($query, $filters->from, $filters->to, $dateColumn);

        return $query;
    }

    /**
     * @param  Builder<OpportunityFollowUp>  $query
     * @return Builder<OpportunityFollowUp>
     */
    public static function applyFollowUpFilters(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        $query->whereHas('opportunity', function (Builder $opportunity) use ($user, $filters): void {
            CrmBranchVisibility::applyOpportunityScope($opportunity, $user);

            if ($filters->branchId !== null) {
                $opportunity->where('branch_id', $filters->branchId);
            }

            if ($filters->campaignId !== null) {
                $opportunity->where('campaign_id', $filters->campaignId);
            }

            if ($filters->clientId !== null) {
                $opportunity->where('client_id', $filters->clientId);
            }

            if ($filters->opportunityId !== null) {
                $opportunity->whereKey($filters->opportunityId);
            }

            if ($filters->leadSourceId !== null) {
                $opportunity->whereHas(
                    'client',
                    fn (Builder $client) => $client->where('lead_source_id', $filters->leadSourceId),
                );
            }
        });

        if ($filters->salesRepId !== null) {
            $query->where('assigned_to', $filters->salesRepId);
        }

        if ($filters->followUpTypeId !== null) {
            $query->where('follow_up_type_id', $filters->followUpTypeId);
        }

        if ($filters->followUpStatusId !== null) {
            $query->where('follow_up_status_id', $filters->followUpStatusId);
        }

        match ($filters->followUpScheduling) {
            'scheduled' => $query->scheduled(),
            'overdue' => $query->overdue(),
            'completed' => $query->completed(),
            default => null,
        };

        $dateColumn = match ($filters->dateBasis) {
            'completed_at' => 'completed_at',
            'created_at' => 'created_at',
            default => 'scheduled_at',
        };

        UserCrmReportQuery::applyDateRange($query, $filters->from, $filters->to, $dateColumn);

        return $query;
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public static function applyCampaignOpportunityFilters(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        CrmBranchVisibility::applyOpportunityScope($query, $user);

        if ($filters->branchId !== null) {
            $query->where('branch_id', $filters->branchId);
        }

        if ($filters->salesRepId !== null) {
            $query->where('assigned_to', $filters->salesRepId);
        }

        if ($filters->leadSourceId !== null) {
            $query->whereHas(
                'client',
                fn (Builder $client) => $client->where('lead_source_id', $filters->leadSourceId),
            );
        }

        if ($filters->campaignId !== null) {
            $query->where('campaign_id', $filters->campaignId);
        }

        if ($filters->dateBasis === 'created_at') {
            UserCrmReportQuery::applyDateRange($query, $filters->from, $filters->to, 'created_at');
        }

        return $query;
    }

    /**
     * @param  Builder<Campaign>  $query
     * @return Builder<Campaign>
     */
    public static function applyCampaignFilters(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        if ($filters->campaignStatus !== null) {
            $query->where('status', $filters->campaignStatus);
        }

        if ($filters->campaignId !== null) {
            $query->whereKey($filters->campaignId);
        }

        $dateBasis = in_array($filters->dateBasis, ['start_date', 'created_at'], true)
            ? $filters->dateBasis
            : 'start_date';

        if ($dateBasis === 'start_date') {
            UserCrmReportQuery::applyDateRange($query, $filters->from, $filters->to, 'start_date');
        }

        $query->whereHas(
            'opportunities',
            fn (Builder $opportunity) => self::applyCampaignOpportunityFilters(
                $opportunity,
                $user,
                self::campaignOpportunityFilters($filters),
            ),
        );

        return $query;
    }

    public static function campaignOpportunityFilters(CrmReportFilters $filters): CrmReportFilters
    {
        return new CrmReportFilters(
            from: $filters->from,
            to: $filters->to,
            dateBasis: $filters->dateBasis === 'created_at' ? 'created_at' : 'created_at',
            branchId: $filters->branchId,
            salesRepId: $filters->salesRepId,
            leadSourceId: $filters->leadSourceId,
            campaignId: $filters->campaignId,
        );
    }

    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    public static function applyClientBranchScope(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        $branchIds = self::effectiveBranchIds($user, $filters);

        if ($branchIds === null) {
            return $query;
        }

        if ($branchIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereHas(
            'opportunities',
            fn (Builder $opportunity) => $opportunity->whereIn('branch_id', $branchIds),
        );
    }

    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    public static function applyClientFilters(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        self::applyClientBranchScope($query, $user, $filters);

        if ($filters->leadSourceId !== null) {
            $query->where('lead_source_id', $filters->leadSourceId);
        }

        if ($filters->clientStage !== null) {
            $query->where('stage', $filters->clientStage);
        }

        if ($filters->salesRepId !== null) {
            $query->where('sales_rep_id', $filters->salesRepId);
        }

        $dateColumn = in_array($filters->dateBasis, ['created_at', 'updated_at'], true)
            ? $filters->dateBasis
            : 'created_at';

        UserCrmReportQuery::applyDateRange($query, $filters->from, $filters->to, $dateColumn);

        if ($filters->hasOpportunities === true) {
            $query->whereHas('opportunities', fn (Builder $opportunity) => self::scopedOpportunityExists($opportunity, $user, $filters));
        } elseif ($filters->hasOpportunities === false) {
            $query->whereDoesntHave('opportunities', fn (Builder $opportunity) => self::scopedOpportunityExists($opportunity, $user, $filters));
        }

        if ($filters->hasWonOpportunity === true) {
            $query->whereHas('opportunities', fn (Builder $opportunity) => self::scopedOpportunityExists($opportunity, $user, $filters)->won());
        } elseif ($filters->hasWonOpportunity === false) {
            $query->whereDoesntHave('opportunities', fn (Builder $opportunity) => self::scopedOpportunityExists($opportunity, $user, $filters)->won());
        }

        return $query;
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    protected static function scopedOpportunityExists(Builder $query, User $user, CrmReportFilters $filters): Builder
    {
        CrmBranchVisibility::applyOpportunityScope($query, $user);

        if ($filters->branchId !== null) {
            $query->where('branch_id', $filters->branchId);
        }

        return $query;
    }

    /**
     * Effective branch restriction for report queries, always intersecting any client-supplied
     * branch filter with the viewer's allowed branches (server-side enforcement).
     *
     * @return list<int>|null null = unrestricted (view-all + no filter); [] = deny (no access);
     *                        non-empty list = restrict to these branch ids
     */
    public static function effectiveBranchIds(User $user, CrmReportFilters $filters): ?array
    {
        if (CrmBranchVisibility::canViewAllBranches($user)) {
            return $filters->branchId !== null ? [$filters->branchId] : null;
        }

        $allowed = CrmBranchVisibility::branchIdsFor($user);

        if ($filters->branchId !== null) {
            return in_array($filters->branchId, $allowed, true) ? [$filters->branchId] : [];
        }

        return $allowed;
    }

    /**
     * Branch ids for aggregate sub-queries. Same contract as {@see effectiveBranchIds()}:
     * null = unrestricted, [] = deny, non-empty list = restrict.
     *
     * @return list<int>|null
     */
    public static function branchIdsForFilters(User $user, CrmReportFilters $filters): ?array
    {
        return self::effectiveBranchIds($user, $filters);
    }
}
