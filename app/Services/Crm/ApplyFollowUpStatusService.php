<?php

namespace App\Services\Crm;

use App\Enums\Crm\FollowUpStatusAction;
use App\Models\Tenant\FollowUpStatus;
use App\Models\Tenant\OpportunityFollowUp;
use App\Models\Tenant\OpportunityStage;
use App\Models\TenantUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyFollowUpStatusService
{
    public function __construct(
        protected ChangeOpportunityStageService $changeOpportunityStageService,
    ) {
    }

    public function handle(
        OpportunityFollowUp $followUp,
        FollowUpStatus $status,
        User $user,
        ?int $targetStageId = null,
        ?Carbon $nextScheduledAt = null,
        ?array $nextFollowUpDefaults = [],
    ): OpportunityFollowUp {
        return DB::transaction(function () use ($followUp, $status, $user, $targetStageId, $nextScheduledAt, $nextFollowUpDefaults): OpportunityFollowUp {
            $followUp->follow_up_status_id = $status->id;

            $action = $status->action ?? FollowUpStatusAction::NONE;
            $meta = $followUp->meta ?? [];

            match ($action) {
                FollowUpStatusAction::SUCCESS_CLOSE => $this->handleClose($followUp, $user, 'success_close', $meta),
                FollowUpStatusAction::FAILED_CLOSE => $this->handleClose($followUp, $user, 'failed_close', $meta),
                FollowUpStatusAction::CHANGE_STAGE => $this->handleChangeStage($followUp, $user, $targetStageId, $meta),
                FollowUpStatusAction::SCHEDULE_NEXT => $this->handleScheduleNext($followUp, $user, $nextScheduledAt, $nextFollowUpDefaults, $meta),
                default => null,
            };

            $followUp->meta = $meta;
            $followUp->save();

            return $followUp->fresh();
        });
    }

    protected function handleClose(
        OpportunityFollowUp $followUp,
        User $user,
        string $stageAction,
        array &$meta,
    ): void {
        $followUp->completed_at = now();

        $opportunity = $followUp->opportunity;
        $targetStage = OpportunityStage::query()->where('action', $stageAction)->first();

        if ($targetStage) {
            $this->changeOpportunityStageService->handle($opportunity, $targetStage, $user);
        } else {
            $opportunity->is_closed = true;
            $opportunity->closed_at = now();
            $opportunity->save();
        }

        $meta['closed_via_follow_up'] = $followUp->id;
    }

    protected function handleChangeStage(
        OpportunityFollowUp $followUp,
        User $user,
        ?int $targetStageId,
        array &$meta,
    ): void {
        if (! $targetStageId) {
            throw ValidationException::withMessages([
                'target_opportunity_stage_id' => __('crm.messages.target_stage_required'),
            ]);
        }

        $followUp->completed_at = now();
        $meta['target_stage_id'] = $targetStageId;

        $this->changeOpportunityStageService->handleByStageId(
            $followUp->opportunity,
            $targetStageId,
            $user,
        );
    }

    protected function handleScheduleNext(
        OpportunityFollowUp $followUp,
        User $user,
        ?Carbon $nextScheduledAt,
        ?array $nextFollowUpDefaults,
        array &$meta,
    ): void {
        if (! $nextScheduledAt) {
            throw ValidationException::withMessages([
                'next_scheduled_at' => __('crm.messages.next_scheduled_at_required'),
            ]);
        }

        $followUp->completed_at = now();
        $meta['scheduled_next_at'] = $nextScheduledAt->toIso8601String();

        $child = OpportunityFollowUp::query()->create([
            'opportunity_id' => $followUp->opportunity_id,
            'parent_follow_up_id' => $followUp->id,
            'follow_up_type_id' => $nextFollowUpDefaults['follow_up_type_id'] ?? $followUp->follow_up_type_id,
            'follow_up_status_id' => $nextFollowUpDefaults['follow_up_status_id'] ?? null,
            'assigned_to' => $nextFollowUpDefaults['assigned_to'] ?? $followUp->assigned_to,
            'created_by' => $user->id,
            'scheduled_at' => $nextScheduledAt,
        ]);

        $meta['rescheduled_to_follow_up'] = $child->id;
    }
}
