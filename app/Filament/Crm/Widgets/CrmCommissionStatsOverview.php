<?php

namespace App\Filament\Crm\Widgets;

use App\Enums\Crm\CommissionStatus;
use App\Filament\Concerns\HasTenantFeatureAccess;
use App\Models\Tenant\OpportunityCommission;
use App\Models\TenantUser;
use App\Support\Money\DecimalMath;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CrmCommissionStatsOverview extends StatsOverviewWidget
{
    use HasTenantFeatureAccess;

    protected static ?int $sort = 2;

    protected int|array|null $columns = 4;

    public static function canView(): bool
    {
        return static::passesTenantFeatureGate() && (Auth::user()?->can('crm_commissions.view_any') ?? false);
    }

    public function getHeading(): ?string
    {
        return __('crm.widgets.commissions_heading');
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof TenantUser) {
            return [];
        }

        $scoped = OpportunityCommission::query()->visibleToUser($user);

        $counts = (clone $scoped)
            ->whereNot('status', CommissionStatus::DRAFT)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $rows = (clone $scoped)
            ->whereNot('status', CommissionStatus::DRAFT)
            ->withFinancialAggregates()
            ->with(['adjustments', 'commissionPayments'])
            ->get();

        $effective = '0.00';
        $netPaid = '0.00';
        $remaining = '0.00';

        foreach ($rows as $commission) {
            if (in_array($commission->status, [CommissionStatus::REJECTED, CommissionStatus::CANCELLED], true)) {
                continue;
            }

            $e = $commission->effectiveCommissionAmount();
            $n = $commission->netPaidAmount();
            $effective = DecimalMath::add($effective, $e);
            $netPaid = DecimalMath::add($netPaid, $n);
            $remaining = DecimalMath::add($remaining, DecimalMath::remaining($e, $n));
        }

        return [
            Stat::make(__('crm.widgets.commission_pending'), (string) ($counts[CommissionStatus::PENDING->value] ?? 0))
                ->color('warning'),
            Stat::make(__('crm.widgets.commission_approved'), (string) ($counts[CommissionStatus::APPROVED->value] ?? 0))
                ->color('info'),
            Stat::make(__('crm.widgets.commission_paid'), (string) ($counts[CommissionStatus::PAID->value] ?? 0))
                ->description(__('crm.widgets.commission_partially_paid', ['count' => (int) ($counts[CommissionStatus::PARTIALLY_PAID->value] ?? 0)]))
                ->color('success'),
            Stat::make(__('crm.widgets.commission_effective'), $this->money($effective))
                ->color('primary'),
            Stat::make(__('crm.widgets.commission_net_paid'), $this->money($netPaid))
                ->color('info'),
            Stat::make(__('crm.widgets.commission_remaining'), $this->money($remaining))
                ->color('warning'),
        ];
    }

    private function money(string $value): string
    {
        return number_format((float) $value, 2).' SAR';
    }
}
