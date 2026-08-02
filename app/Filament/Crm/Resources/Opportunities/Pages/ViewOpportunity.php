<?php

namespace App\Filament\Crm\Resources\Opportunities\Pages;

use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOpportunity extends ViewRecord
{
    protected static string $resource = OpportunityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
