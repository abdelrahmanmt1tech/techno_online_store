<?php

namespace App\Services\Crm\Commission;

use App\Enums\Crm\CommissionStatus;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Crm\Commission\OwnCommissionVisibility;
use Illuminate\Database\Eloquent\Builder;

final class OwnCommissionQuery
{
    public static function baseForUser(User $user): Builder
    {
        return OpportunityCommission::query()
            ->forUser($user->id)
            ->withFinancialAggregates()
            ->with(['opportunity.client', 'branch']);
    }

    public static function forUser(
        User $user,
        bool $includeHistory = false,
        bool $includeDraft = false,
    ): Builder {
        return self::baseForUser($user)
            ->whereIn('status', OwnCommissionVisibility::statusesForList($includeHistory, $includeDraft));
    }

    public static function forAuthenticatedUser(
        bool $includeHistory = false,
        bool $includeDraft = false,
    ): Builder {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return self::forUser($user, $includeHistory, $includeDraft);
    }

    public static function applyPaymentSettlementFilter(Builder $query, ?string $settlement): Builder
    {
        return match ($settlement) {
            'fully_paid' => $query->where('status', CommissionStatus::PAID),
            'partially_paid' => $query->where('status', CommissionStatus::PARTIALLY_PAID),
            'unpaid' => $query->where('status', CommissionStatus::APPROVED),
            default => $query,
        };
    }
}
