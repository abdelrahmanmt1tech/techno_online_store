<?php

namespace App\Services\Crm;

use App\Models\Tenant\Client;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityFollowUp;
use Illuminate\Database\Eloquent\Builder;

class UserCrmReportQuery
{
    public static function applyDateRange(Builder $query, ?string $from, ?string $to, string $column): Builder
    {
        if (filled($from)) {
            $query->whereDate($column, '>=', $from);
        }

        if (filled($to)) {
            $query->whereDate($column, '<=', $to);
        }

        return $query;
    }

    public static function opportunitiesForUser(int $userId, string $scope, ?string $from, ?string $to): Builder
    {
        $column = $scope === 'assigned' ? 'assigned_to' : 'created_by';

        return self::applyDateRange(
            Opportunity::query()
                ->where($column, $userId)
                ->with(['client', 'opportunityStage', 'branch', 'assignedTo']),
            $from,
            $to,
            'created_at',
        );
    }

    public static function followUpsForUser(int $userId, string $scope, ?string $from, ?string $to): Builder
    {
        $column = $scope === 'assigned' ? 'assigned_to' : 'created_by';

        return self::applyDateRange(
            OpportunityFollowUp::query()
                ->where($column, $userId)
                ->with(['opportunity.client', 'followUpType', 'followUpStatus', 'assignedTo']),
            $from,
            $to,
            'scheduled_at',
        );
    }

    /**
     * @return array{open: int, won: int, lost: int, closed: int, amount: float, agreed_amount: float, won_amount: float, won_agreed_amount: float}
     */
    public static function opportunitySummary(int $userId, ?string $from, ?string $to, string $scope = 'assigned'): array
    {
        $base = self::opportunitiesForUser($userId, $scope, $from, $to);

        $open = (clone $base)->open()->count();
        $won = (clone $base)->won()->count();
        $lost = (clone $base)->lost()->count();
        $closed = (clone $base)->closed()->count();

        $wonQuery = (clone $base)->won();

        return [
            'open' => $open,
            'won' => $won,
            'lost' => $lost,
            'closed' => $closed,
            'amount' => (float) (clone $base)->sum('amount'),
            'agreed_amount' => (float) (clone $base)->sum('agreed_amount'),
            'won_amount' => (float) (clone $wonQuery)->sum('amount'),
            'won_agreed_amount' => (float) (clone $wonQuery)->sum('agreed_amount'),
        ];
    }

    /**
     * @return array{pending: int, overdue: int, completed: int, scheduled: int}
     */
    public static function followUpSummary(int $userId, ?string $from, ?string $to, string $scope = 'assigned'): array
    {
        $base = self::followUpsForUser($userId, $scope, $from, $to);

        return [
            'pending' => (clone $base)->whereNull('completed_at')->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'completed' => (clone $base)->whereNotNull('completed_at')->count(),
            'scheduled' => (clone $base)->whereNull('completed_at')->where('scheduled_at', '>=', now())->count(),
        ];
    }

    public static function clientsForUser(int $userId, string $scope, ?string $from, ?string $to): Builder
    {
        $column = $scope === 'sales_rep' ? 'sales_rep_id' : 'first_followed_by';

        return self::withClientCrmCounts(
            Client::query()->where($column, $userId)->with('leadSource'),
            $from,
            $to,
        );
    }

    public static function withClientCrmCounts(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query->withCount([
            'opportunities as opportunities_won_count' => fn (Builder $q) => self::applyDateRange((clone $q)->won(), $from, $to, 'created_at'),
            'opportunities as opportunities_lost_count' => fn (Builder $q) => self::applyDateRange((clone $q)->lost(), $from, $to, 'created_at'),
            'opportunities as opportunities_open_count' => fn (Builder $q) => self::applyDateRange((clone $q)->open(), $from, $to, 'created_at'),
            'opportunityFollowUps as follow_ups_completed_count' => fn (Builder $q) => self::applyDateRange((clone $q)->whereNotNull('completed_at'), $from, $to, 'scheduled_at'),
            'opportunityFollowUps as follow_ups_overdue_count' => fn (Builder $q) => self::applyDateRange((clone $q)->overdue(), $from, $to, 'scheduled_at'),
            'opportunityFollowUps as follow_ups_pending_count' => fn (Builder $q) => self::applyDateRange(
                (clone $q)->whereNull('completed_at')->where('scheduled_at', '>=', now()),
                $from,
                $to,
                'scheduled_at',
            ),
        ]);
    }

    public static function countClientsForUser(int $userId, string $scope): int
    {
        $column = $scope === 'sales_rep' ? 'sales_rep_id' : 'first_followed_by';

        return Client::query()->where($column, $userId)->count();
    }

    /**
     * @return array{won: int, lost: int, open: int}
     */
    public static function clientOpportunityTotals(int $userId, string $scope, ?string $from, ?string $to): array
    {
        $clientIds = self::clientIdsForUser($userId, $scope);

        if ($clientIds === []) {
            return ['won' => 0, 'lost' => 0, 'open' => 0];
        }

        $base = self::applyDateRange(
            Opportunity::query()->whereIn('client_id', $clientIds),
            $from,
            $to,
            'created_at',
        );

        return [
            'won' => (clone $base)->won()->count(),
            'lost' => (clone $base)->lost()->count(),
            'open' => (clone $base)->open()->count(),
        ];
    }

    /**
     * @return array{completed: int, overdue: int, pending: int}
     */
    public static function clientFollowUpTotals(int $userId, string $scope, ?string $from, ?string $to): array
    {
        $clientIds = self::clientIdsForUser($userId, $scope);

        if ($clientIds === []) {
            return ['completed' => 0, 'overdue' => 0, 'pending' => 0];
        }

        $base = self::applyDateRange(
            OpportunityFollowUp::query()->whereHas('opportunity', fn (Builder $q) => $q->whereIn('client_id', $clientIds)),
            $from,
            $to,
            'scheduled_at',
        );

        return [
            'completed' => (clone $base)->whereNotNull('completed_at')->count(),
            'overdue' => (clone $base)->overdue()->count(),
            'pending' => (clone $base)->whereNull('completed_at')->where('scheduled_at', '>=', now())->count(),
        ];
    }

    /**
     * @return list<int>
     */
    protected static function clientIdsForUser(int $userId, string $scope): array
    {
        $column = $scope === 'sales_rep' ? 'sales_rep_id' : 'first_followed_by';

        return Client::query()
            ->where($column, $userId)
            ->pluck('id')
            ->all();
    }
}
