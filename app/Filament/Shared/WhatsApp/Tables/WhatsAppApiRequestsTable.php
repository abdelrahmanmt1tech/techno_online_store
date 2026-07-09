<?php

namespace App\Filament\Shared\WhatsApp\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppApiRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('summary')
                    ->label(__('dashboard.whatsapp_log_summary'))
                    ->limit(70)
                    ->searchable(),
                TextColumn::make('operation')
                    ->label(__('dashboard.whatsapp_api_operation'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                TextColumn::make('status_label')
                    ->label(__('dashboard.whatsapp_api_status_label'))
                    ->limit(40),
                TextColumn::make('outcome')
                    ->label(__('dashboard.whatsapp_api_outcome'))
                    ->badge()
                    ->color(fn ($state) => $state?->value === 'success' ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                TextColumn::make('recipient_phone')
                    ->label(__('dashboard.whatsapp_customer_phone'))
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('http_status')
                    ->label(__('dashboard.whatsapp_http_status'))
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
