<?php

namespace App\Filament\SharedForms;

use Filament\Tables\Columns\TextColumn;

class NotesTable
{
    public static function latestNoteColumn(?string $modalHeading = null): TextColumn
    {
        $modalHeading ??= __('crm.notes.modal_heading');

        return TextColumn::make('latestNote.note')
            ->label(__('crm.fields.latest_note'))
            ->limit(100)
            ->placeholder('-')
            ->extraAttributes(['class' => 'cursor-pointer text-primary underline'])
            ->action(NotesActions::viewNotesAction('viewNotesFromColumn', $modalHeading));
    }
}
