<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\Pages;

use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Models\Tenant\OpportunityFollowUp;
use App\Services\Crm\PersistOpportunityFollowUpService;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class CreateOpportunityFollowUp extends CreateRecord
{
    protected static string $resource = OpportunityFollowUpResource::class;

    public function mount(): void
    {
        parent::mount();

        $fill = [
            'created_by' => Auth::id(),
            'assigned_to' => Auth::id(),
            'scheduled_at' => now(),
        ];

        $opportunityId = request()->integer('opportunity_id');

        if ($opportunityId > 0) {
            $fill['opportunity_id'] = $opportunityId;
        }

        $parentId = request()->integer('parent_follow_up_id');

        if ($parentId > 0) {
            $fill['parent_follow_up_id'] = $parentId;
            $parent = OpportunityFollowUp::query()->find($parentId);

            if ($parent) {
                $fill['opportunity_id'] ??= $parent->opportunity_id;
            }
        }

        $this->form->fill($fill);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        return app(PersistOpportunityFollowUpService::class)->handle(
            new OpportunityFollowUp(),
            $data,
            $user,
        );
    }

    protected function getRedirectUrl(): string
    {
        return OpportunityFollowUpResource::getUrl('view', ['record' => $this->record]);
    }
}
