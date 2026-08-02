<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\Pages;

use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Filament\SharedForms\NotesActions;
use App\Models\Tenant\OpportunityFollowUp;
use App\Services\Crm\PersistOpportunityFollowUpService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class EditOpportunityFollowUp extends EditRecord
{
    protected static string $resource = OpportunityFollowUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_opportunity')
                ->label(__('crm.actions.view_opportunity'))
                ->icon(Heroicon::RectangleStack)
                ->url(fn (): string => OpportunityResource::getUrl('view', ['record' => $this->record->opportunity_id], panel: 'crm')),
            Action::make('view_client')
                ->label(__('crm.actions.view_client'))
                ->icon(Heroicon::TenantUser)
                ->visible(fn (): bool => (bool) $this->record->opportunity?->client_id)
                ->url(fn (): string => ClientResource::getUrl('view', ['record' => $this->record->opportunity->client_id])),
            NotesActions::addNoteAction(),
            NotesActions::viewNotesAction(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        return app(PersistOpportunityFollowUpService::class)->handle(
            $record,
            $data,
            $user,
        );
    }

    protected function getRedirectUrl(): string
    {
        return OpportunityFollowUpResource::getUrl('view', ['record' => $this->record]);
    }
}
