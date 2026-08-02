<?php

namespace App\Services\Crm\Reports;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\Client;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityCommission;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\TenantUser;
use App\Services\Crm\UserCrmReportQuery;
use App\Support\Money\DecimalMath;
use Illuminate\Database\Eloquent\Builder;

final class EmployeePerformanceReportQuery
{
    /** @var array<int, array{effective: string, net_paid: string, remaining: string}>|null */
    protected static ?array $commissionTotalsCache = null;

    public static function flushCommissionTotalsCache(): void
    {
        self::$commissionTotalsCache = null;
    }

    /**
     * @return Builder<TenantUser>
     */
    public static function tableQuery(TenantUser $user, CrmReportFilters $filters): Builder
    {
        $employeeIds = self::employeeIdsInScope($user, $filters);

        $query = TenantUser::query()
            ->select('users.*')
            ->whereIn('users.id', $employeeIds);

        self::applyAggregates($query, $user, $filters);
        self::warmCommissionTotalsCache($user, $filters, $employeeIds);

        return $query->orderBy('users.name');
    }

    /**
     * @param  list<int>  $employeeIds
     */
    protected static function warmCommissionTotalsCache(TenantUser $viewer, CrmReportFilters $filters, array $employeeIds): void
    {
        if ($employeeIds === []) {
            self::$commissionTotalsCache = [];

            return;
        }

        self::$commissionTotalsCache = self::buildCommissionTotals($viewer, $filters, $employeeIds);
    }

    /**
     * @return array{effective: string, net_paid: string, remaining: string}
     */
    public static function commissionTotalsFor(int $employeeId): array
    {
        return self::$commissionTotalsCache[$employeeId] ?? [
            'effective' => '0.00',
            'net_paid' => '0.00',
            'remaining' => '0.00',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(TenantUser $user, CrmReportFilters $filters): array
    {
        $employeeIds = self::employeeIdsInScope($user, $filters);
        $commissionTotals = self::buildCommissionTotals($user, $filters, $employeeIds);

        $oppBase = Opportunity::query();
        CrmReportScope::applyOpportunityFilters($oppBase, $user, $filters);

        if ($filters->salesRepId !== null) {
            $oppBase->where('assigned_to', $filters->salesRepId);
        }

        $total = (clone $oppBase)->count();
        $open = (clone $oppBase)->open()->count();
        $won = (clone $oppBase)->won()->count();
        $lost = (clone $oppBase)->lost()->count();
        $closed = $won + $lost;

        $amountTotal = DecimalMath::normalize((string) (clone $oppBase)->sum('amount'));
        $agreedTotal = DecimalMath::normalize((string) (clone $oppBase)->sum('agreed_amount'));

        $followUpBase = OpportunityFollowUp::query();
        CrmReportScope::applyFollowUpFilters($followUpBase, $user, self::followUpFilters($filters));
        $completedFollowUps = (clone $followUpBase)->completed()->count();
        $overdueFollowUps = (clone $followUpBase)->overdue()->count();

        $effectiveTotal = '0.00';
        $netPaidTotal = '0.00';
        $remainingTotal = '0.00';

        foreach ($commissionTotals as $totals) {
            $effectiveTotal = DecimalMath::add($effectiveTotal, $totals['effective']);
            $netPaidTotal = DecimalMath::add($netPaidTotal, $totals['net_paid']);
            $remainingTotal = DecimalMath::add($remainingTotal, $totals['remaining']);
        }

        $averageCloseDays = (clone $oppBase)
            ->closed()
            ->whereNotNull('closed_at')
            ->selectRaw('AVG(DATEDIFF(closed_at, created_at)) as avg_days')
            ->value('avg_days');

        return [
            'employees_count' => count($employeeIds),
            'clients_total' => self::clientsCount($user, $filters),
            'opportunities_total' => $total,
            'open_total' => $open,
            'won_total' => $won,
            'lost_total' => $lost,
            'amount_total' => $amountTotal,
            'agreed_amount_total' => $agreedTotal,
            'conversion_rate' => CrmReportMetrics::conversionRate($won, $closed),
            'average_close_days' => $averageCloseDays !== null ? round((float) $averageCloseDays, 1) : null,
            'completed_follow_ups' => $completedFollowUps,
            'overdue_follow_ups' => $overdueFollowUps,
            'effective_commissions_total' => $effectiveTotal,
            'net_paid_total' => $netPaidTotal,
            'remaining_total' => $remainingTotal,
            'rankings' => self::rankings($user, $filters, $employeeIds),
        ];
    }

    public static function conversionRate(TenantUser $employee): string
    {
        $closed = (int) $employee->won_opportunities_count + (int) $employee->lost_opportunities_count;

        return CrmReportMetrics::conversionRate((int) $employee->won_opportunities_count, $closed);
    }

    public static function followUpCompletionRate(TenantUser $employee): string
    {
        $completed = (int) $employee->completed_follow_ups_count;
        $overdue = (int) $employee->overdue_follow_ups_count;
        $denominator = $completed + $overdue;

        return CrmReportMetrics::conversionRate($completed, $denominator);
    }

    public static function averageCloseDays(TenantUser $employee): ?float
    {
        if ($employee->average_close_days === null) {
            return null;
        }

        return round((float) $employee->average_close_days, 1);
    }

    /**
     * @param  Builder<TenantUser>  $query
     */
    protected static function applyAggregates(Builder $query, TenantUser $user, CrmReportFilters $filters): void
    {
        $query->withCount([
            'salesRepClients as clients_count' => fn (Builder $q) => self::applyClientSideFilters($q, $user, $filters),
            'assignedOpportunities as opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters),
            'assignedOpportunities as open_opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)->open(),
            'assignedOpportunities as won_opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)->won(),
            'assignedOpportunities as lost_opportunities_count' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)->lost(),
            'assignedFollowUps as completed_follow_ups_count' => fn (Builder $q) => self::applyFollowUpSideFilters($q, $user, $filters)->completed(),
            'assignedFollowUps as overdue_follow_ups_count' => fn (Builder $q) => self::applyFollowUpSideFilters($q, $user, $filters)->overdue(),
        ])->withSum(
            ['assignedOpportunities as amount_total' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)],
            'amount',
        )->withSum(
            ['assignedOpportunities as agreed_amount_total' => fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters)],
            'agreed_amount',
        );

        $query->selectSub(
            (clone Opportunity::query())
                ->selectRaw('AVG(DATEDIFF(closed_at, created_at))')
                ->whereColumn('assigned_to', 'users.id')
                ->tap(fn (Builder $q) => self::applyOpportunitySideFilters($q, $user, $filters))
                ->closed()
                ->whereNotNull('closed_at'),
            'average_close_days',
        );
    }

    /**
     * @return list<int>
     */
    protected static function employeeIdsInScope(TenantUser $user, CrmReportFilters $filters): array
    {
        $fromOpportunities = Opportunity::query()
            ->tap(fn (Builder $q) => CrmReportScope::applyOpportunityFilters($q, $user, $filters))
            ->when(
                $filters->salesRepId !== null,
                fn (Builder $q) => $q->where('assigned_to', $filters->salesRepId),
            )
            ->distinct()
            ->pluck('assigned_to');

        $fromClients = Client::query()
            ->tap(fn (Builder $q) => CrmReportScope::applyClientFilters($q, $user, self::clientFilters($filters)))
            ->when(
                $filters->salesRepId !== null,
                fn (Builder $q) => $q->where('sales_rep_id', $filters->salesRepId),
            )
            ->distinct()
            ->pluck('sales_rep_id');

        $fromFollowUps = OpportunityFollowUp::query()
            ->tap(fn (Builder $q) => CrmReportScope::applyFollowUpFilters($q, $user, self::followUpFilters($filters)))
            ->distinct()
            ->pluck('assigned_to');

        return $fromOpportunities
            ->merge($fromClients)
            ->merge($fromFollowUps)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected static function clientsCount(TenantUser $user, CrmReportFilters $filters): int
    {
        $query = Client::query();
        CrmReportScope::applyClientFilters($query, $user, self::clientFilters($filters));

        if ($filters->salesRepId !== null) {
            $query->where('sales_rep_id', $filters->salesRepId);
        }

        return $query->count();
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array<int, array{effective: string, net_paid: string, remaining: string}>
     */
    protected static function buildCommissionTotals(TenantUser $viewer, CrmReportFilters $filters, array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $query = OpportunityCommission::query()
            ->visibleToUser($viewer)
            ->withFinancialAggregates()
            ->whereIn('user_id', $employeeIds)
            ->where('status', '!=', CommissionStatus::DRAFT);

        if ($filters->branchId !== null) {
            $query->where('branch_id', $filters->branchId);
        }

        $dateColumn = in_array($filters->dateBasis, ['created_at', 'approved_at'], true)
            ? $filters->dateBasis
            : 'created_at';

        UserCrmReportQuery::applyDateRange($query, $filters->from, $filters->to, $dateColumn);

        if ($filters->salesRepId !== null) {
            $query->where('user_id', $filters->salesRepId);
        }

        $totals = [];
        foreach ($employeeIds as $employeeId) {
            $totals[$employeeId] = [
                'effective' => '0.00',
                'net_paid' => '0.00',
                'remaining' => '0.00',
            ];
        }

        foreach ($query->get() as $commission) {
            $effective = $commission->effectiveCommissionAmount();
            $netPaid = $commission->netPaidAmount();
            $remaining = DecimalMath::remaining($effective, $netPaid);
            $userId = (int) $commission->user_id;

            $totals[$userId]['effective'] = DecimalMath::add($totals[$userId]['effective'], $effective);
            $totals[$userId]['net_paid'] = DecimalMath::add($totals[$userId]['net_paid'], $netPaid);
            $totals[$userId]['remaining'] = DecimalMath::add($totals[$userId]['remaining'], $remaining);
        }

        return $totals;
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array<string, list<string>>
     */
    protected static function rankings(TenantUser $user, CrmReportFilters $filters, array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [
                'by_won' => [],
                'by_agreed_amount' => [],
                'by_conversion' => [],
                'by_follow_up_completion' => [],
            ];
        }

        $rows = TenantUser::query()
            ->whereIn('users.id', $employeeIds)
            ->tap(fn (Builder $q) => self::applyAggregates($q, $user, $filters))
            ->get();

        $byWon = $rows->sortByDesc('won_opportunities_count')->take(5)->pluck('name')->all();
        $byAgreed = $rows->sortByDesc('agreed_amount_total')->take(5)->pluck('name')->all();
        $byConversion = $rows
            ->sortByDesc(fn (TenantUser $employee): float => (float) self::conversionRate($employee))
            ->take(5)
            ->pluck('name')
            ->all();
        $byFollowUp = $rows
            ->sortByDesc(fn (TenantUser $employee): float => (float) self::followUpCompletionRate($employee))
            ->take(5)
            ->pluck('name')
            ->all();

        return [
            'by_won' => $byWon,
            'by_agreed_amount' => $byAgreed,
            'by_conversion' => $byConversion,
            'by_follow_up_completion' => $byFollowUp,
        ];
    }

    /**
     * @param  Builder<Client>  $query
     * @return Builder<Client>
     */
    protected static function applyClientSideFilters(Builder $query, TenantUser $user, CrmReportFilters $filters): Builder
    {
        return CrmReportScope::applyClientFilters($query, $user, self::clientFilters($filters));
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    protected static function applyOpportunitySideFilters(Builder $query, TenantUser $user, CrmReportFilters $filters): Builder
    {
        return CrmReportScope::applyOpportunityFilters($query, $user, $filters);
    }

    /**
     * @param  Builder<OpportunityFollowUp>  $query
     * @return Builder<OpportunityFollowUp>
     */
    protected static function applyFollowUpSideFilters(Builder $query, TenantUser $user, CrmReportFilters $filters): Builder
    {
        return CrmReportScope::applyFollowUpFilters($query, $user, self::followUpFilters($filters));
    }

    protected static function clientFilters(CrmReportFilters $filters): CrmReportFilters
    {
        return new CrmReportFilters(
            from: $filters->from,
            to: $filters->to,
            dateBasis: in_array($filters->dateBasis, ['created_at', 'updated_at'], true) ? $filters->dateBasis : 'created_at',
            branchId: $filters->branchId,
            salesRepId: $filters->salesRepId,
            leadSourceId: $filters->leadSourceId,
        );
    }

    protected static function followUpFilters(CrmReportFilters $filters): CrmReportFilters
    {
        return new CrmReportFilters(
            from: $filters->from,
            to: $filters->to,
            dateBasis: in_array($filters->dateBasis, ['scheduled_at', 'completed_at', 'created_at'], true)
                ? $filters->dateBasis
                : 'scheduled_at',
            branchId: $filters->branchId,
            salesRepId: $filters->salesRepId,
            leadSourceId: $filters->leadSourceId,
            campaignId: $filters->campaignId,
            clientId: $filters->clientId,
            opportunityId: $filters->opportunityId,
        );
    }
}
