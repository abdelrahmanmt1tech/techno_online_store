<?php

namespace App\Filament\Shared\WhatsApp\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('profile_name')
                    ->label(__('dashboard.whatsapp_customer_name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('phone')
                    ->label(__('dashboard.whatsapp_customer_phone'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('last_message_at')
                    ->label(__('dashboard.whatsapp_last_message'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_message_at', 'desc');
    }
}
