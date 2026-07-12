<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContactInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('message')
                    ->label(__('dashboard.message'))
                    ->columnSpanFull()
                    ->icon('heroicon-o-chat-bubble-left'),
            ])
            ->columns(2);
    }
}
