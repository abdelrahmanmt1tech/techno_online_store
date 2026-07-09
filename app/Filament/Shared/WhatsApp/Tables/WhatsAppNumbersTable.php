<?php

namespace App\Filament\Shared\WhatsApp\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsAppNumbersTable
{
    public static function configure(Table $table, bool $showTenant = false): Table
    {
        $columns = [];

        if ($showTenant) {
            $columns[] = TextColumn::make('tenant.name')
                ->label(__('dashboard.whatsapp_tenant'))
                ->searchable()
                ->sortable();
        }

        return $table
            ->columns([
                ...$columns,
                TextColumn::make('display_phone_number')
                    ->label(__('dashboard.whatsapp_display_phone'))
                    ->searchable(),
                TextColumn::make('phone_number_id')
                    ->label(__('dashboard.whatsapp_phone_number_id'))
                    ->copyable()
                    ->searchable(),
                TextColumn::make('whatsapp_business_account_id')
                    ->label(__('dashboard.whatsapp_waba_id'))
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('dashboard.whatsapp_connection_status'))
                    ->badge(),
                IconColumn::make('is_default')
                    ->label(__('dashboard.whatsapp_is_default'))
                    ->boolean(),
                TextColumn::make('last_inbound_at')
                    ->label(__('dashboard.whatsapp_last_inbound'))
                    ->dateTime()
                    ->toggleable(),
                TextColumn::make('last_outbound_at')
                    ->label(__('dashboard.whatsapp_last_outbound'))
                    ->dateTime()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
