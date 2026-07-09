<?php

namespace App\Filament\Shared\WhatsApp\Schemas;

use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppNumberForm
{
    public static function configure(Schema $schema, bool $showToken = true): Schema
    {
        return $schema->components([
            Section::make(__('dashboard.whatsapp_number'))
                ->columns(2)
                ->schema([
                    TextInput::make('display_phone_number')
                        ->label(__('dashboard.whatsapp_display_phone'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('phone_number_id')
                        ->label(__('dashboard.whatsapp_phone_number_id'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('whatsapp_business_account_id')
                        ->label(__('dashboard.whatsapp_waba_id'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('business_name')
                        ->label(__('dashboard.whatsapp_business_name'))
                        ->maxLength(255),

                    Select::make('status')
                        ->label(__('dashboard.whatsapp_connection_status'))
                        ->options(collect(WhatsAppConnectionStatus::cases())->mapWithKeys(
                            fn (WhatsAppConnectionStatus $status) => [$status->value => __('dashboard.whatsapp_status_'.$status->value)]
                        ))
                        ->default(WhatsAppConnectionStatus::Active->value)
                        ->required()
                        ->native(false),

                    TextInput::make('webhook_status')
                        ->label(__('dashboard.whatsapp_webhook_status'))
                        ->maxLength(255),

                    Toggle::make('is_default')
                        ->label(__('dashboard.whatsapp_is_default')),

                    Toggle::make('is_active')
                        ->label(__('dashboard.active'))
                        ->default(true),

                    ...($showToken ? [
                        TextInput::make('access_token')
                            ->label(__('dashboard.whatsapp_access_token'))
                            ->password()
                            ->revealable(false)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(__('dashboard.whatsapp_token_helper'))
                            ->columnSpanFull(),

                        Placeholder::make('masked_token')
                            ->label(__('dashboard.whatsapp_access_token'))
                            ->content(fn ($record) => $record?->masked_access_token ?: '—')
                            ->visible(fn (string $operation): bool => $operation === 'edit')
                            ->columnSpanFull(),
                    ] : []),
                ])
                ->columnSpanFull(),
        ]);
    }
}
