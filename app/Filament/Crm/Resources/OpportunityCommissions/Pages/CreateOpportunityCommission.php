<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Pages;

use App\Filament\Crm\Resources\OpportunityCommissions\OpportunityCommissionResource;
use App\Services\Crm\Commission\OpportunityCommissionWorkflowService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateOpportunityCommission extends CreateRecord
{
    protected static string $resource = OpportunityCommissionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        return app(OpportunityCommissionWorkflowService::class)->create($data, $user);
    }
}
