<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Pages;

use App\Filament\Crm\Resources\OpportunityCommissions\Actions\OpportunityCommissionActions;
use App\Filament\Crm\Resources\OpportunityCommissions\OpportunityCommissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOpportunityCommission extends ViewRecord
{
    protected static string $resource = OpportunityCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...OpportunityCommissionActions::headerActions($this->record),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
