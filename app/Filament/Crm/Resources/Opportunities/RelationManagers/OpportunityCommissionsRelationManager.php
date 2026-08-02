<?php

namespace App\Filament\Crm\Resources\Opportunities\RelationManagers;

use App\Filament\Crm\Resources\OpportunityCommissions\OpportunityCommissionResource;
use App\Filament\Crm\Resources\OpportunityCommissions\Schemas\OpportunityCommissionForm;
use App\Filament\Crm\Resources\OpportunityCommissions\Tables\OpportunityCommissionColumns;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityCommission;
use App\Services\Crm\Commission\OpportunityCommissionWorkflowService;
use App\Support\Crm\Commission\OpportunityCommissionAccess;
use App\Support\Crm\CrmBranchVisibility;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OpportunityCommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'opportunityCommissions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('crm.commissions.relation_title');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $user = Auth::user();

        if (! $user?->can('crm_commissions.view_any')) {
            return false;
        }

        if (! $ownerRecord instanceof Opportunity) {
            return false;
        }

        if (CrmBranchVisibility::canViewAllBranches($user)) {
            return true;
        }

        $branchIds = CrmBranchVisibility::branchIdsFor($user);

        if ($branchIds === [] || $ownerRecord->branch_id === null) {
            return false;
        }

        return in_array((int) $ownerRecord->branch_id, $branchIds, true);
    }

    public function form(Schema $schema): Schema
    {
        /** @var Opportunity $owner */
        $owner = $this->getOwnerRecord();

        return OpportunityCommissionForm::configure($schema, $owner);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $user = Auth::user();

                if ($user) {
                    $query->visibleToUser($user);
                }
            })
            ->columns(OpportunityCommissionColumns::make())
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->visible(fn (): bool => Auth::user() !== null
                        && app(OpportunityCommissionWorkflowService::class)
                            ->canCreateForOpportunity(Auth::user(), $this->getOwnerRecord()))
                    ->mutateFormDataUsing(function (array $data): array {
                        /** @var Opportunity $owner */
                        $owner = $this->getOwnerRecord();
                        $data['opportunity_id'] = $owner->id;

                        return $data;
                    })
                    ->using(function (array $data, OpportunityCommissionWorkflowService $workflow): OpportunityCommission {
                        $user = Auth::user();
                        abort_unless($user !== null, 403);

                        return $workflow->create($data, $user);
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(fn (OpportunityCommission $record): string => OpportunityCommissionResource::getUrl('view', ['record' => $record])),
                EditAction::make()
                    ->visible(fn (OpportunityCommission $record): bool => Auth::user() !== null
                        && OpportunityCommissionAccess::canUpdate(Auth::user(), $record)),
                DeleteAction::make()
                    ->visible(fn (OpportunityCommission $record): bool => Auth::user() !== null
                        && OpportunityCommissionAccess::canDelete(Auth::user(), $record)),
            ]);
    }
}
