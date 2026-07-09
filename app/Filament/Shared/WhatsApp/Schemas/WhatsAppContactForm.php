<?php

namespace App\Filament\Shared\WhatsApp\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('dashboard.whatsapp_contact'))
                ->columns(2)
                ->schema([
                    TextInput::make('phone')
                        ->label(__('dashboard.whatsapp_customer_phone'))
                        ->tel()
                        ->required()
                        ->maxLength(32)
                        ->unique(ignoreRecord: true)
                        ->helperText(__('dashboard.whatsapp_contact_phone_helper')),

                    TextInput::make('profile_name')
                        ->label(__('dashboard.whatsapp_customer_name'))
                        ->maxLength(255),
                ])
                ->columnSpanFull(),
        ]);
    }
}
