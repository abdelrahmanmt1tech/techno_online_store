<?php

namespace App\Filament\Shared\WhatsApp\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppWebhookEventsTable
{
    public static function configure(Table $table, bool $includeTenant = false): Table
    {
        $columns = [];

        if ($includeTenant) {
            $columns[] = TextColumn::make('tenant.name')
                ->label(__('dashboard.whatsapp_tenant'))
                ->placeholder('—');
        }

        $columns = array_merge($columns, [
            TextColumn::make('summary')
                ->label(__('dashboard.whatsapp_log_summary'))
                ->limit(60)
                ->searchable()
                ->placeholder('—'),
            TextColumn::make('event_type')
                ->label(__('dashboard.whatsapp_event_type'))
                ->badge()
                ->placeholder('—'),
            TextColumn::make('processing_status')
                ->label(__('dashboard.whatsapp_processing_status'))
                ->badge()
                ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
            TextColumn::make('phone_number_id')
                ->label(__('dashboard.whatsapp_phone_number_id'))
                ->toggleable(),
            TextColumn::make('error_message')
                ->label(__('dashboard.description'))
                ->limit(40)
                ->toggleable(),
            TextColumn::make('created_at')
                ->label(__('dashboard.created_at'))
                ->dateTime()
                ->sortable(),
        ]);

        return $table
            ->columns($columns)
            ->defaultSort('created_at', 'desc');
    }
}
