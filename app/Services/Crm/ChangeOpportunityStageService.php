<?php

namespace App\Services\Crm;

use App\Enums\Crm\OpportunityStageAction;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityStage;
use App\Models\Tenant\OpportunityStageLog;
use App\Models\TenantUser;
use App\Services\Crm\Commission\AutomaticOpportunityCommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeOpportunityStageService
{
    public function __construct(
        private readonly AutomaticOpportunityCommissionService $automaticCommissionService,
    ) {}

    public function handle(
        Opportunity $opportunity,
        OpportunityStage $newStage,
        TenantUser $user,
        ?string $notes = null,
        ?array $meta = null,
    ): void {
        if ($opportunity->opportunity_stage_id === $newStage->id) {
            return;
        }

        DB::transaction(function () use ($opportunity, $newStage, $user, $notes, $meta): void {
            $fromStageId = $opportunity->opportunity_stage_id;
            $newAction = $newStage->action ?? OpportunityStageAction::NONE;

            $opportunity->opportunity_stage_id = $newStage->id;
            $opportunity->applyStageAction($newAction);

            $opportunity->save();

            OpportunityStageLog::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => $fromStageId,
                'to_stage_id' => $newStage->id,
                'changed_by' => $user->id,
                'notes' => $notes,
                'meta' => $meta,
            ]);

            // Atomic: an auto commission is a direct financial consequence of closing as won.
            // Idempotent + gracefully skips (logged) when no employee / percentage / base / duplicate.
            $this->automaticCommissionService->handleStageTransition($opportunity, $newAction, $user);
        });
    }

    public function handleByStageId(
        Opportunity $opportunity,
        int $newStageId,
        TenantUser $user,
        ?string $notes = null,
        ?array $meta = null,
    ): void {
        $newStage = OpportunityStage::query()->find($newStageId);

        if (! $newStage) {
            throw ValidationException::withMessages([
                'opportunity_stage_id' => __('crm.messages.invalid_stage'),
            ]);
        }

        $this->handle($opportunity, $newStage, $user, $notes, $meta);
    }

    /**
     * Record the initial stage on opportunity creation (stage is already persisted on the model).
     */
    public function recordInitialStage(
        Opportunity $opportunity,
        OpportunityStage $stage,
        TenantUser $user,
        ?string $notes = null,
        ?array $meta = null,
    ): void {
        DB::transaction(function () use ($opportunity, $stage, $user, $notes, $meta): void {
            $opportunity->applyStageAction($stage->action ?? OpportunityStageAction::NONE);

            $opportunity->save();

            OpportunityStageLog::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_stage_id' => null,
                'to_stage_id' => $stage->id,
                'changed_by' => $user->id,
                'notes' => $notes,
                'meta' => $meta,
            ]);
        });
    }
}
