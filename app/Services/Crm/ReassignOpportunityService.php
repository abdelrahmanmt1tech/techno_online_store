<?php

namespace App\Services\Crm;

use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityAssignmentLog;
use App\Models\TenantUser;
use Illuminate\Support\Facades\DB;

class ReassignOpportunityService
{
    public function handle(
        Opportunity $opportunity,
        ?int $newAssigneeId,
        TenantUser $changedBy,
        ?string $notes = null,
    ): void {
        if ($opportunity->assigned_to === $newAssigneeId) {
            return;
        }

        DB::transaction(function () use ($opportunity, $newAssigneeId, $changedBy, $notes): void {
            $fromUserId = $opportunity->assigned_to;

            $opportunity->assigned_to = $newAssigneeId;

            if ($opportunity->first_assigned_to === null && $newAssigneeId !== null) {
                $opportunity->first_assigned_to = $newAssigneeId;
            }

            $opportunity->save();

            OpportunityAssignmentLog::query()->create([
                'opportunity_id' => $opportunity->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $newAssigneeId,
                'changed_by' => $changedBy->id,
                'notes' => $notes,
            ]);
        });
    }
}
