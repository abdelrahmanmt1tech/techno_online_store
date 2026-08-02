<?php

namespace App\Filament\Crm\Resources\Opportunities\Pages;

use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Services\Crm\CreateOpportunityService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateOpportunity extends CreateRecord
{
    protected static string $resource = OpportunityResource::class;

    public function mount(): void
    {
        parent::mount();

        $fill = [
            'created_by' => Auth::id(),
            'assigned_to' => Auth::id(),
        ];

        $clientId = request()->integer('client_id');

        if ($clientId > 0) {
            $fill['client_id'] = $clientId;
        }

        $this->form->fill($fill);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        return app(CreateOpportunityService::class)->handle($data, $user);
    }
}
