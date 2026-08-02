<?php

namespace App\Filament\Crm\Resources\CommissionPaymentCycles\RelationManagers;

use App\Filament\Crm\Resources\CommissionPaymentCycles\Actions\CommissionPaymentActions;
use App\Filament\Crm\Resources\CommissionPaymentCycles\Tables\CommissionPaymentColumns;
use App\Models\Tenant\CommissionPaymentCycle;
use App\Support\Crm\Commission\CommissionPaymentCycleAccess;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CommissionPaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissionPayments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('crm.payment_cycles.payments.relation_title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = Auth::user();

        if ($user === null || ! $ownerRecord instanceof CommissionPaymentCycle) {
            return false;
        }

        return CommissionPaymentCycleAccess::canView($user, $ownerRecord);
    }

    public function table(Table $table): Table
    {
        $cycle = $this->getOwnerCycle();

        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $user = Auth::user();

                if ($user === null) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query
                    ->with(['user', 'opportunityCommission.opportunity', 'executedBy', 'reversals'])
                    ->visibleToUser($user);
            })
            ->columns(CommissionPaymentColumns::make())
            ->defaultSort('executed_at', 'desc')
            ->emptyStateHeading(__('crm.payment_cycles.payments.empty'))
            ->recordActions([
                CommissionPaymentActions::reverseAction($cycle),
            ])
            ->headerActions([])
            ->bulkActions([])
            ->checkIfRecordIsSelectableUsing(fn (): bool => false);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    protected function getOwnerCycle(): CommissionPaymentCycle
    {
        $owner = $this->getOwnerRecord();

        abort_unless($owner instanceof CommissionPaymentCycle, 404);

        return $owner;
    }
}
