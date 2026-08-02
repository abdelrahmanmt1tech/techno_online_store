<?php

namespace App\Filament\Tenant\Resources\Contacts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ContactInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('branch.name')
                    ->label(__('dashboard.branch'))
                    ->icon('heroicon-o-building-office-2'),

                TextEntry::make('job')
                    ->label(__('dashboard.job'))
                    ->icon('heroicon-o-briefcase'),

                TextEntry::make('message')
                    ->label(__('dashboard.message'))
                    ->columnSpanFull()
                    ->icon('heroicon-o-chat-bubble-left'),
            ])
            ->columns(2);
    }
}
