<?php

namespace App\Services\Crm;

use App\Models\Tenant\FollowUpStatus;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\TenantUser;
use Illuminate\Support\Carbon;

class PersistOpportunityFollowUpService
{
    public function __construct(
        protected ApplyFollowUpStatusService $applyFollowUpStatusService,
    ) {}

    public function handle(
        OpportunityFollowUp $followUp,
        array $data,
        User $user,
        ?int $opportunityId = null,
    ): OpportunityFollowUp {
        $targetStageId = $data['target_opportunity_stage_id'] ?? null;
        $nextScheduledAt = isset($data['next_scheduled_at']) ? Carbon::parse($data['next_scheduled_at']) : null;

        $nextFollowUpDefaults = array_filter([
            'assigned_to' => $data['next_assigned_to'] ?? null,
            'follow_up_type_id' => $data['next_follow_up_type_id'] ?? null,
        ], fn ($value): bool => $value !== null && $value !== '');

        unset(
            $data['target_opportunity_stage_id'],
            $data['next_scheduled_at'],
            $data['next_assigned_to'],
            $data['next_follow_up_type_id'],
        );

        $statusId = $data['follow_up_status_id'] ?? null;
        unset($data['follow_up_status_id']);

        $oldStatusId = $followUp->exists ? $followUp->follow_up_status_id : null;

        $followUp->fill($data);

        if ($statusId && $oldStatusId === $statusId) {
            $followUp->follow_up_status_id = $statusId;
        }

        if (! $followUp->exists) {
            $followUp->opportunity_id = $opportunityId ?? $data['opportunity_id'] ?? $followUp->opportunity_id;
            $followUp->created_by ??= $user->id;
        }

        $followUp->save();

        if ($statusId && $oldStatusId !== $statusId) {
            $status = FollowUpStatus::query()->findOrFail($statusId);

            $this->applyFollowUpStatusService->handle(
                $followUp->fresh(),
                $status,
                $user,
                $targetStageId ? (int) $targetStageId : null,
                $nextScheduledAt,
                $nextFollowUpDefaults,
            );
        }

        return $followUp->fresh();
    }
}
