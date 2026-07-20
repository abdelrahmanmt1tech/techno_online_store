<?php

namespace App\Filament\Tenant\Resources\Contacts\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('dashboard.name'))
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('dashboard.email'))
                    ->required()
                    ->email()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label(__('dashboard.customer_phone'))
                    ->maxLength(255),

                Textarea::make('message')
                    ->label(__('dashboard.message'))
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
