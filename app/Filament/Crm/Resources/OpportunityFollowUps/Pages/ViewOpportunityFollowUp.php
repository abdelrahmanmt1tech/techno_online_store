<?php

namespace App\Filament\Crm\Resources\OpportunityFollowUps\Pages;

use App\Filament\Crm\Resources\Opportunities\OpportunityResource;
use App\Filament\Crm\Resources\OpportunityFollowUps\OpportunityFollowUpResource;
use App\Filament\Tenant\Resources\Clients\ClientResource;
use App\Filament\SharedForms\NotesActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOpportunityFollowUp extends ViewRecord
{
    protected static string $resource = OpportunityFollowUpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_opportunity')
                ->label(__('crm.actions.view_opportunity'))
                ->icon(Heroicon::RectangleStack)
                ->color('primary')
                ->url(fn (): string => OpportunityResource::getUrl('view', ['record' => $this->record->opportunity_id], panel: 'crm')),
            Action::make('view_client')
                ->label(__('crm.actions.view_client'))
                ->icon(Heroicon::User)
                ->color('gray')
                ->visible(fn (): bool => (bool) $this->record->opportunity?->client_id)
                ->url(fn (): string => ClientResource::getUrl('view', ['record' => $this->record->opportunity->client_id])),
            Action::make('create_child_follow_up')
                ->label(__('crm.actions.create_follow_up'))
                ->icon(Heroicon::Plus)
                ->color('success')
                ->url(fn (): string => OpportunityFollowUpResource::getUrl('create', [
                    'opportunity_id' => $this->record->opportunity_id,
                    'parent_follow_up_id' => $this->record->getKey(),
                ], panel: 'crm')),
            NotesActions::addNoteAction(),
            NotesActions::viewNotesAction(),
            EditAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
