<?php

namespace App\Filament\Crm\Resources\OpportunityCommissions\Pages;

use App\Filament\Crm\Resources\OpportunityCommissions\Actions\OpportunityCommissionActions;
use App\Filament\Crm\Resources\OpportunityCommissions\OpportunityCommissionResource;
use App\Services\Crm\Commission\OpportunityCommissionWorkflowService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditOpportunityCommission extends EditRecord
{
    protected static string $resource = OpportunityCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...OpportunityCommissionActions::headerActions($this->record),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        return app(OpportunityCommissionWorkflowService::class)->update($record, $data, $user);
    }
}
