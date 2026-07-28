<?php

namespace App\Filament\Tenant\Resources\CashierSessions\Tables;

use App\Enums\Pos\CashierSessionStatus;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashierSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('register.name')->label(__('commerce.fields.register'))->searchable()->sortable(),
                TextColumn::make('user.name')->label(__('commerce.fields.cashier'))->searchable(),
                TextColumn::make('status')
                    ->label(__('erp.fields.status'))
                    ->formatStateUsing(fn ($state) => $state instanceof CashierSessionStatus ? $state->label() : (string) $state)
                    ->badge(),
                TextColumn::make('opened_at')->label(__('commerce.fields.opened_at'))->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('closed_at')->label(__('commerce.fields.closed_at'))->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('opening_balance')->label(__('commerce.fields.opening_balance')),
                TextColumn::make('actual_balance')->label(__('commerce.fields.actual_balance')),
                TextColumn::make('difference')->label(__('commerce.fields.difference')),
            ])
            ->filters([
                SelectFilter::make('pos_register_id')
                    ->label(__('commerce.fields.register'))
                    ->relationship('register', 'name')
                    ->native(false),
                SelectFilter::make('status')
                    ->label(__('erp.fields.status'))
                    ->options(ErpEnumOptions::options(CashierSessionStatus::class))
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
