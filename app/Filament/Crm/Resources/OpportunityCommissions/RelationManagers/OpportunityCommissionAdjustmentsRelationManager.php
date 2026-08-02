<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\RelationManagers;

use App\Filament\Crm\Resources\OpportunityCommissions\Actions\OpportunityCommissionAdjustmentActions;
use App\Filament\Crm\Resources\OpportunityCommissions\Tables\OpportunityCommissionAdjustmentColumns;
use App\Models\Tenant\OpportunityCommission;
use App\Support\Crm\Commission\OpportunityCommissionAccess;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OpportunityCommissionAdjustmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'adjustments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('crm.commissions.adjustments.relation_title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = Auth::user();

        if ($user === null || ! $user->can('crm_commissions.view_adjustments')) {
            return false;
        }

        if (! $ownerRecord instanceof OpportunityCommission) {
            return false;
        }

        return OpportunityCommissionAccess::canView($user, $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $user = Auth::user();

                if ($user === null) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query
                    ->with(['createdBy', 'approvedBy', 'rejectedBy', 'commission'])
                    ->whereHas('commission', function (Builder $commission) use ($user): void {
                        $commission->visibleToUser($user);
                    });
            })
            ->columns(OpportunityCommissionAdjustmentColumns::make())
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('crm.commissions.adjustments.empty'))
            ->headerActions([
                OpportunityCommissionAdjustmentActions::createAction($this->getOwnerCommission()),
            ])
            ->recordActions(OpportunityCommissionAdjustmentActions::recordActions())
            ->bulkActions([])
            ->checkIfRecordIsSelectableUsing(fn (): bool => false);
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    protected function getOwnerCommission(): OpportunityCommission
    {
        $owner = $this->getOwnerRecord();

        abort_unless($owner instanceof OpportunityCommission, 404);

        $owner->loadMissing('adjustments', 'commissionPayments');

        return $owner;
    }
}
