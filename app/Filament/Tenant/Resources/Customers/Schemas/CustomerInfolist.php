<?php

namespace App\Filament\Tenant\Resources\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('dashboard.customer'))
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('dashboard.name'))
                            ->icon('heroicon-o-user-circle'),

                        TextEntry::make('user.name')                                                                                                                                                                                             
                            ->label(__('dashboard.linked_user'))
                            ->icon('heroicon-o-link')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make(__('dashboard.customer_contacts'))
                    ->icon('heroicon-o-phone')
                    ->schema([
                        TextEntry::make('primary_phone')
                            ->label(__('dashboard.customer_phone'))
                            ->icon('heroicon-o-phone')
                            ->state(fn ($record) => $record->primaryPhone() ?? '-'),

                        TextEntry::make('primary_email')
                            ->label(__('dashboard.customer_email'))
                            ->icon('heroicon-o-envelope')
                            ->state(fn ($record) => $record->primaryEmail() ?? '-'),

                        TextEntry::make('primary_whatsapp')                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      
                            ->label(__('dashboard.whatsapp'))
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->state(fn ($record) => $record->primaryWhatsapp() ?? '-'),
                    ])
                    ->columns(3),

                Section::make(__('dashboard.orders'))
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        TextEntry::make('orders_count')
                            ->state(fn ($record) => $record->orders_count ?? 0)
                            ->label(__('dashboard.orders'))
                            ->icon('heroicon-o-shopping-bag'),

                        TextEntry::make('created_at')
                            ->label(__('dashboard.created_at'))
                            ->icon('heroicon-o-calendar')
                            ->dateTime('Y-m-d H:i'),
                    ])
                    ->columns(2),
            ]);
    }
}

