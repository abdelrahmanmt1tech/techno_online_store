<?php

namespace App\Filament\Crm\Resources\OpportunityStages\Pages;

use App\Filament\Crm\Resources\OpportunityStages\OpportunityStageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOpportunityStages extends ManageRecords
{
    protected static string $resource = OpportunityStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
