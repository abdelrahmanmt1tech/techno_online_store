<?php

namespace App\Filament\SharedForms;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class NotesFormSchema
{
    public static function make(): array
    {
        return [
            Textarea::make('note')
                ->label(__('crm.fields.note'))
                ->columnSpanFull()
                ->required()
                ->maxLength(255),
            Toggle::make('is_private')
                ->label(__('crm.fields.is_private'))
                ->default(false),
        ];
    }
}
