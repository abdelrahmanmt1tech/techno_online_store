<?php

namespace App\Services\Crm\Reports;

use App\Models\Tenant\Campaign;
use App\Models\Tenant\Opportunity;
use App\Models\TenantUser;
use App\Services\Crm\UserCrmReportQuery;
use App\Support\Money\DecimalMath;
use Illuminate\Database\Eloquent\Builder;

final class CampaignReportQuery
{
    /**
     * @return Builder<Campaign>
     */
    public static function tableQuery(TenantUser $user, CrmReportFilters $filters): Builder
    {
        $query = Campaign::query()->select('campaigns.*');

        CrmReportScope::applyCampaignFilters($query, $user, $filters);
        self::applyAggregates($query, $user, $filters);

        return $query->orderByDesc('campaigns.start_date');
    }

    /**
     * @param  Builder<Campaign>  $query
     */
    protected static function applyAggregates(Builder $query, TenantUser $user, CrmReportFilters $filters): void
    {
        $oppFilters = CrmReportScope::campaignOpportunityFilters($filters);

        $query->withCount([
            'opportunities as opportunities_count' => fn (Builder $q) => CrmReportScope::applyCampaignOpportunityFilters($q, $user, $oppFilters),
            'opportunities as won_opportunities_count' => fn (Builder $q) => CrmReportScope::applyCampaignOpportunityFilters($q, $user, $oppFilters)->won(),
            'opportunities as lost_opportunities_count' => fn (Builder $q) => CrmReportScope::applyCampaignOpportunityFilters($q, $user, $oppFilters)->lost(),
        ])->withSum(
            ['opportunities as amount_total' => fn (Builder $q) => CrmReportScope::applyCampaignOpportunityFilters($q, $user, $oppFilters)],
            'amount',
        )->withSum(
            ['opportunities as agreed_amount_total' => fn (Builder $q) => CrmReportScope::applyCampaignOpportunityFilters($q, $user, $oppFilters)],
            'agreed_amount',
        )->withSum(
            ['opportunities as won_agreed_amount_total' => fn (Builder $q) => CrmReportScope::applyCampaignOpportunityFilters($q, $user, $oppFilters)->won()],
            'agreed_amount',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        $base = Campaign::query();
        CrmReportScope::applyCampaignFilters($base, $user, $filters);

        $campaignCount = (clone $base)->count();

        $oppBase = Opportunity::query();
        CrmReportScope::applyCampaignOpportunityFilters($oppBase, $user, CrmReportScope::campaignOpportunityFilters($filters));

        if ($filters->campaignId !== null) {
            $oppBase->where('campaign_id', $filters->campaignId);
        }

        if ($filters->campaignStatus !== null) {
            $oppBase->whereHas('campaign', fn (Builder $campaign) => $campaign->where('status', $filters->campaignStatus));
        }

        if ($filters->dateBasis === 'start_date') {
            $oppBase->whereHas('campaign', function (Builder $campaign) use ($filters): void {
                UserCrmReportQuery::applyDateRange($campaign, $filters->from, $filters->to, 'start_date');
            });
        }

        $opportunitiesTotal = (clone $oppBase)->count();
        $wonTotal = (clone $oppBase)->won()->count();
        $lostTotal = (clone $oppBase)->lost()->count();
        $closedTotal = $wonTotal + $lostTotal;

        $amountTotal = DecimalMath::normalize((string) (clone $oppBase)->sum('amount'));
        $agreedTotal = DecimalMath::normalize((string) (clone $oppBase)->sum('agreed_amount'));
        $wonAgreedTotal = DecimalMath::normalize((string) (clone $oppBase)->won()->sum('agreed_amount'));
        $budgetTotal = DecimalMath::normalize((string) (clone $base)->sum('budget'));

        return [
            'campaigns_count' => $campaignCount,
            'opportunities_total' => $opportunitiesTotal,
            'won_total' => $wonTotal,
            'lost_total' => $lostTotal,
            'amount_total' => $amountTotal,
            'agreed_amount_total' => $agreedTotal,
            'won_agreed_amount_total' => $wonAgreedTotal,
            'budget_total' => $budgetTotal,
            'conversion_rate' => CrmReportMetrics::conversionRate($wonTotal, $closedTotal),
            'expected_roi' => CrmReportMetrics::expectedRoiPercent($wonAgreedTotal, $budgetTotal),
            'by_status' => self::groupByStatus($base),
        ];
    }

    public static function conversionRate(Campaign $campaign): string
    {
        $closed = (int) $campaign->won_opportunities_count + (int) $campaign->lost_opportunities_count;

        return CrmReportMetrics::conversionRate((int) $campaign->won_opportunities_count, $closed);
    }

    public static function costPerOpportunity(Campaign $campaign): string
    {
        $count = (int) $campaign->opportunities_count;

        if ($count === 0) {
            return CrmReportMetrics::NOT_APPLICABLE;
        }

        return CrmReportMetrics::divideOrNa(
            DecimalMath::normalize((string) $campaign->budget),
            (string) $count,
        );
    }

    public static function costPerWonOpportunity(Campaign $campaign): string
    {
        $count = (int) $campaign->won_opportunities_count;

        if ($count === 0) {
            return CrmReportMetrics::NOT_APPLICABLE;
        }

        return CrmReportMetrics::divideOrNa(
            DecimalMath::normalize((string) $campaign->budget),
            (string) $count,
        );
    }

    public static function expectedRoi(Campaign $campaign): string
    {
        $wonAgreed = DecimalMath::normalize((string) ($campaign->won_agreed_amount_total ?? '0'));

        return CrmReportMetrics::expectedRoiPercent(
            $wonAgreed,
            DecimalMath::normalize((string) $campaign->budget),
        );
    }

    /**
     * @param  Builder<Campaign>  $base
     * @return array<string, int>
     */
    protected static function groupByStatus(Builder $base): array
    {
        $rows = (clone $base)
            ->select('status')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $status = (string) ($row->status ?? '');
            $label = $status !== ''
                ? __('crm.campaign_status_options.'.$status)
                : __('crm.reports.common.not_specified');
            $result[$label] = (int) $row->total;
        }

        return $result;
    }
}
