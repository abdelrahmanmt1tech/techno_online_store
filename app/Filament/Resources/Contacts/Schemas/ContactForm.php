<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('inquiry_type')
                    ->label(__('dashboard.inquiry_type'))
                    ->options([
                        'general' => __('dashboard.general_inquiry'),
                        'support' => __('dashboard.tech_support'),
                        'complaint' => __('dashboard.complaint'),
                        'suggestion' => __('dashboard.suggestion'),
                        'partnership' => __('dashboard.partnership'),
                    ])
                    ->default('general'),

                TextInput::make('subject')
                    ->label(__('dashboard.subject'))
                    ->maxLength(255),

                TextInput::make('name')
                    ->label(__('dashboard.name'))
                    ->maxLength(255),

                TextInput::make('email')
                    ->label(__('dashboard.email'))
                    ->email()
                    ->maxLength(255),

                Textarea::make('message')
                    ->label(__('dashboard.message'))
                    ->columnSpanFull(),

                FileUpload::make('file_path')
                    ->label(__('dashboard.attachment'))
                    ->directory('contacts/attachments')
                    ->disk('public')
                // ->acceptedFileTypes(['.pdf', '.doc', '.docx', '.jpg', '.jpeg', '.png'])
                    ->maxSize(5120),
            ]);
    }
}
