<?php

namespace App\Filament\Shared\Messenger\Schemas;

use App\Messenger\Enums\MessengerPageStatus;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MessengerPageForm
{
    public static function configure(Schema $schema, bool $showToken = true): Schema
    {
        return $schema->components([
            Section::make(__('dashboard.messenger_page'))
                ->columns(2)
                ->schema([
                    TextInput::make('page_id')
                        ->label(__('dashboard.messenger_page_id'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('page_name')
                        ->label(__('dashboard.messenger_page_name'))
                        ->maxLength(255),

                    Select::make('status')
                        ->label(__('dashboard.messenger_connection_status'))
                        ->options(collect(MessengerPageStatus::cases())->mapWithKeys(
                            fn (MessengerPageStatus $status) => [$status->value => __('dashboard.messenger_status_'.$status->value)]
                        ))
                        ->default(MessengerPageStatus::Active->value)
                        ->required()
                        ->native(false),

                    TextInput::make('webhook_status')
                        ->label(__('dashboard.messenger_webhook_status'))
                        ->maxLength(255),

                    Toggle::make('is_default')
                        ->label(__('dashboard.messenger_is_default')),

                    Toggle::make('is_active')
                        ->label(__('dashboard.active'))
                        ->default(true),

                    ...($showToken ? [
                        TextInput::make('page_access_token')
                            ->label(__('dashboard.messenger_page_access_token'))
                            ->password()
                            ->revealable(false)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText(__('dashboard.messenger_token_helper'))
                            ->columnSpanFull(),

                        Placeholder::make('masked_token')
                            ->label(__('dashboard.messenger_page_access_token'))
                            ->content(fn ($record) => $record?->masked_page_access_token ?: '—')
                            ->visible(fn (string $operation): bool => $operation === 'edit')
                            ->columnSpanFull(),
                    ] : []),
                ])
                ->columnSpanFull(),

            Section::make(__('dashboard.messenger_page_diagnostics'))
                ->columns(2)
                ->visible(fn (string $operation): bool => $operation === 'edit')
                ->schema([
                    Placeholder::make('last_inbound_at')
                        ->label(__('dashboard.messenger_last_inbound'))
                        ->content(fn ($record) => $record?->last_inbound_at?->toDateTimeString() ?: '—'),
                    Placeholder::make('last_outbound_at')
                        ->label(__('dashboard.messenger_last_outbound'))
                        ->content(fn ($record) => $record?->last_outbound_at?->toDateTimeString() ?: '—'),
                    Placeholder::make('last_error_message')
                        ->label(__('dashboard.messenger_last_error'))
                        ->content(fn ($record) => $record?->last_error_message ?: '—')
                        ->columnSpanFull(),
                    Placeholder::make('connected_at')
                        ->label(__('dashboard.messenger_connected_at'))
                        ->content(fn ($record) => $record?->connected_at?->toDateTimeString() ?: '—'),
                    Placeholder::make('disconnected_at')
                        ->label(__('dashboard.messenger_disconnected_at'))
                        ->content(fn ($record) => $record?->disconnected_at?->toDateTimeString() ?: '—'),
                    Placeholder::make('reconnect_required_at')
                        ->label(__('dashboard.messenger_reconnect_required_at'))
                        ->content(fn ($record) => $record?->reconnect_required_at?->toDateTimeString() ?: '—'),
                ])
                ->columnSpanFull(),
        ]);
    }
}
