<?php

namespace App\Services\Crm;

use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityStage;
use App\Models\TenantUser;

class CreateOpportunityService
{
    public function __construct(
        protected ChangeOpportunityStageService $changeOpportunityStageService,
        protected ReassignOpportunityService $reassignOpportunityService,
    ) {}

    public function handle(array $data, TenantUser $user): Opportunity
    {
        $assigneeId = $data['assigned_to'] ?? null;
        unset($data['assigned_to'], $data['first_assigned_to']);

        $data['created_by'] = $user->id;

        $opportunity = Opportunity::query()->create($data);

        if ($opportunity->opportunity_stage_id) {
            $stage = OpportunityStage::query()->find($opportunity->opportunity_stage_id);

            if ($stage) {
                $this->changeOpportunityStageService->recordInitialStage(
                    $opportunity,
                    $stage,
                    $user,
                );
            }
        }

        if ($assigneeId) {
            $this->reassignOpportunityService->handle(
                $opportunity->fresh(),
                (int) $assigneeId,
                $user,
            );
        }

        return $opportunity->fresh();
    }
}
