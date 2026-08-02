<?php

namespace App\Filament\Crm\Resources\Opportunities\Pages;

use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Models\TenantUser;
use App\Services\Crm\ChangeOpportunityStageService;
use App\Services\Crm\ReassignOpportunityService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOpportunity extends EditRecord
{
    protected static string $resource = OpportunityResource::class;

    protected ?int $pendingStageId = null;

    protected ?int $originalStageId = null;

    protected bool $assigneeChanged = false;

    protected ?int $pendingAssigneeId = null;

    protected ?int $originalAssigneeId = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['opportunity_stage_id']) && (int) $data['opportunity_stage_id'] !== (int) $this->record->opportunity_stage_id) {
            $this->pendingStageId = (int) $data['opportunity_stage_id'];
            $this->originalStageId = (int) $this->record->opportunity_stage_id;
            $data['opportunity_stage_id'] = $this->record->opportunity_stage_id;
        }

        if (array_key_exists('assigned_to', $data) && (int) ($data['assigned_to'] ?? 0) !== (int) ($this->record->assigned_to ?? 0)) {
            $this->assigneeChanged = true;
            $this->pendingAssigneeId = $data['assigned_to'] ? (int) $data['assigned_to'] : null;
            $this->originalAssigneeId = $this->record->assigned_to !== null ? (int) $this->record->assigned_to : null;
            $data['assigned_to'] = $this->record->assigned_to;
        }

        unset($data['first_assigned_to'], $data['is_closed'], $data['closed_at']);

        return $data;
    }

    protected function afterSave(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        // The stage/assignee selects use ->relationship(), so Filament's saveRelationships()
        // writes the new values directly after save. Reset the in-memory record back to the
        // original value so the services detect the change and run their side effects (e.g. close).
        if ($this->pendingStageId) {
            $record = $this->record->fresh();
            $record->opportunity_stage_id = $this->originalStageId;

            app(ChangeOpportunityStageService::class)->handleByStageId(
                $record,
                $this->pendingStageId,
                $user,
            );
        }

        if ($this->assigneeChanged) {
            $record = $this->record->fresh();
            $record->assigned_to = $this->originalAssigneeId;

            app(ReassignOpportunityService::class)->handle(
                $record,
                $this->pendingAssigneeId,
                $user,
            );
        }
    }
}
