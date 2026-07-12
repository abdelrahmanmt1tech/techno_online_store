<?php

namespace App\Filament\Shared\Messenger\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MessengerPagesTable
{
    public static function configure(Table $table, bool $showTenant = false): Table
    {
        $columns = [];

        if ($showTenant) {
            $columns[] = TextColumn::make('tenant.name')
                ->label(__('dashboard.messenger_tenant'))
                ->searchable()
                ->sortable();
        }

        return $table
            ->columns([
                ...$columns,
                TextColumn::make('page_name')
                    ->label(__('dashboard.messenger_page_name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('page_id')
                    ->label(__('dashboard.messenger_page_id'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('dashboard.messenger_connection_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum
                        ? __('dashboard.messenger_status_'.$state->value)
                        : (string) $state),
                TextColumn::make('webhook_status')
                    ->label(__('dashboard.messenger_webhook_status'))
                    ->toggleable()
                    ->placeholder('—'),
                IconColumn::make('is_default')
                    ->label(__('dashboard.messenger_is_default'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(__('dashboard.active'))
                    ->boolean(),
                TextColumn::make('last_inbound_at')
                    ->label(__('dashboard.messenger_last_inbound'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('last_outbound_at')
                    ->label(__('dashboard.messenger_last_outbound'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('last_error_message')
                    ->label(__('dashboard.messenger_last_error'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
