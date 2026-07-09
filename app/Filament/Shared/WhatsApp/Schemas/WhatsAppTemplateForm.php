<?php

namespace App\Filament\Shared\WhatsApp\Schemas;

use App\WhatsApp\Enums\WhatsAppTemplateCategory;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WhatsAppTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('dashboard.whatsapp_template'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('dashboard.whatsapp_template_name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('language')
                        ->label(__('dashboard.whatsapp_template_language'))
                        ->required()
                        ->default(config('whatsapp.default_locale'))
                        ->maxLength(20),

                    TextInput::make('whatsapp_business_account_id')
                        ->label(__('dashboard.whatsapp_waba_id'))
                        ->required()
                        ->maxLength(255),

                    Select::make('category')
                        ->label(__('dashboard.whatsapp_template_category'))
                        ->options(collect(WhatsAppTemplateCategory::cases())->mapWithKeys(
                            fn (WhatsAppTemplateCategory $category) => [$category->value => $category->value]
                        ))
                        ->required()
                        ->native(false),

                    Select::make('status')
                        ->label(__('dashboard.whatsapp_template_status'))
                        ->options(collect(WhatsAppTemplateStatus::cases())->mapWithKeys(
                            fn (WhatsAppTemplateStatus $status) => [$status->value => $status->value]
                        ))
                        ->default(WhatsAppTemplateStatus::Approved->value)
                        ->required()
                        ->native(false),

                    Select::make('whatsapp_number_id')
                        ->label(__('dashboard.whatsapp_number'))
                        ->relationship('whatsappNumber', 'display_phone_number')
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->native(false),

                    Toggle::make('is_disabled_locally')
                        ->label(__('dashboard.whatsapp_disable')),

                    Textarea::make('components')
                        ->label(__('dashboard.whatsapp_template_components'))
                        ->rows(6)
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                        ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                        ->columnSpanFull(),

                    Textarea::make('variables_schema')
                        ->label(__('dashboard.whatsapp_template_variables_schema'))
                        ->rows(4)
                        ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                        ->dehydrateStateUsing(fn (?string $state) => filled($state) ? json_decode($state, true) : null)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
