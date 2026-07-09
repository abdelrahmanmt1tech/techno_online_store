<?php

namespace App\Filament\Shared\WhatsApp\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.whatsapp_template_name'))
                    ->searchable(),
                TextColumn::make('language')
                    ->label(__('dashboard.whatsapp_template_language')),
                TextColumn::make('category')
                    ->label(__('dashboard.whatsapp_template_category'))
                    ->badge(),
                TextColumn::make('status')
                    ->label(__('dashboard.whatsapp_template_status'))
                    ->badge(),
                TextColumn::make('whatsappNumber.display_phone_number')
                    ->label(__('dashboard.whatsapp_number'))
                    ->toggleable(),
                IconColumn::make('is_disabled_locally')
                    ->label(__('dashboard.whatsapp_disable'))
                    ->boolean(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
