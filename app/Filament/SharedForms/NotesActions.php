<?php

namespace App\Filament\SharedForms;

use App\Services\Crm\CreateNoteService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NotesActions
{
    public static function addNoteAction(?string $name = 'add_note'): Action
    {
        return Action::make($name)
            ->color('warning')
            ->label(__('crm.notes.add'))
            ->icon('heroicon-m-chat-bubble-left-right')
            ->schema(NotesFormSchema::make())
            ->action(function (Model $record, array $data, CreateNoteService $createNoteService): void {
                $user = Auth::user();

                if (! $user) {
                    return;
                }

                $createNoteService->handle(
                    $record,
                    $data['note'],
                    (bool) ($data['is_private'] ?? false),
                    $user,
                );

                Notification::make()
                    ->title(__('crm.notes.created'))
                    ->success()
                    ->send();
            });
    }

    public static function viewNotesAction(?string $name = 'viewNotes', ?string $modalHeading = null): Action
    {
        $modalHeading ??= __('crm.notes.modal_heading');

        return Action::make($name)
            ->label(__('crm.notes.view_all'))
            ->modalHeading($modalHeading)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('crm.actions.close'))
            ->modalContent(function (Model $record) {
                $user = Auth::user();
                $query = $record->notes()->with('createdBy')->latest();

                if ($user) {
                    $query->visibleTo($user);
                }

                return view('filament.notes-modal', [
                    'record' => $record,
                    'notes' => $query->get(),
                ]);
            });
    }
}
