<?php

namespace App\Filament\Crm\Pages;

use App\Enums\Crm\CommissionAdjustmentDirection;
use App\Enums\Crm\CommissionAdjustmentStatus;
use App\Enums\Crm\CommissionPaymentEntryType;
use App\Enums\Crm\CommissionStatus;
use App\Enums\Crm\CommissionType;
use App\Enums\PaymentMethod;
use App\Filament\Crm\CrmPage;
use App\Models\Tenant\CommissionPayment;
use App\Models\Tenant\OpportunityCommission;
use App\Models\Tenant\OpportunityCommissionAdjustment;
use App\Models\TenantUser;
use App\Support\Crm\Commission\OwnCommissionAccess;
use Illuminate\Support\Facades\Auth;

class ViewMyCommission extends CrmPage
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'my-commissions/{commission}';

    protected string $view = 'filament.crm.pages.view-my-commission';

    public OpportunityCommission $record;

    public function mount(int|string $commission): void
    {
        $user = Auth::user();
        abort_unless($user instanceof TenantUser, 403);

        $record = OpportunityCommission::query()
            ->forUser($user->id)
            ->withFinancialAggregates()
            ->with([
                'opportunity.client',
                'branch',
                'approvedBy',
                'adjustments' => fn ($query) => $query->orderByDesc('created_at'),
                'commissionPayments.commissionPaymentCycle',
                'commissionPayments.executedBy',
                'auditLogs.user',
            ])
            ->findOrFail($commission);

        abort_unless(OwnCommissionAccess::canViewCommission($user, $record), 403);

        $this->record = $record;
    }

    public static function canAccessByPermission(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser && OwnCommissionAccess::canViewPage($user);
    }

    public function getTitle(): string
    {
        return __('crm.own_commissions.detail_title', [
            'opportunity' => $this->record->opportunity?->title ?? '#'.$this->record->opportunity_id,
        ]);
    }

    public function canViewPayments(): bool
    {
        $user = Auth::user();

        return $user instanceof TenantUser && OwnCommissionAccess::canViewPayments($user);
    }

    /**
     * @return list<CommissionPayment>
     */
    public function getPaymentsProperty(): array
    {
        if (! $this->canViewPayments()) {
            return [];
        }

        return $this->record->commissionPayments
            ->sortByDesc('executed_at')
            ->values()
            ->all();
    }

    /**
     * @return list<OpportunityCommissionAdjustment>
     */
    public function getAdjustmentsProperty(): array
    {
        return $this->record->adjustments->all();
    }

    public function formatMoney(string $amount): string
    {
        return number_format((float) $amount, 2, '.', ',');
    }

    public function statusLabel(CommissionStatus $status): string
    {
        return $status->label();
    }

    public function typeLabel(CommissionType $type): string
    {
        return $type->label();
    }

    public function adjustmentDirectionLabel(CommissionAdjustmentDirection $direction): string
    {
        return $direction->label();
    }

    public function adjustmentStatusLabel(CommissionAdjustmentStatus $status): string
    {
        return $status->label();
    }

    public function paymentEntryLabel(CommissionPaymentEntryType $type): string
    {
        return $type->label();
    }

    public function paymentMethodLabel(?string $method): string
    {
        if ($method === null || $method === '') {
            return '-';
        }

        return PaymentMethod::tryFrom($method)?->label() ?? $method;
    }
}
