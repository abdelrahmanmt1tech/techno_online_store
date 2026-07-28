<?php

namespace App\Filament\Tenant\Resources\CashMovements\Tables;

use App\Enums\Pos\CashMovementType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('session.id')->label(__('commerce.resources.cashier_session'))->sortable(),
                TextColumn::make('type')
                    ->label(__('commerce.fields.type'))
                    ->formatStateUsing(fn ($state) => $state instanceof CashMovementType ? $state->label() : (string) $state)
                    ->badge(),
                TextColumn::make('payment_method_type')->label(__('commerce.fields.payment_method_type')),
                TextColumn::make('direction')->label(__('commerce.fields.direction')),
                TextColumn::make('amount')->label(__('commerce.fields.amount')),
                IconColumn::make('is_reversal')->label('Reversal')->boolean(),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ErpEnumOptions::options(CashMovementType::class))
                    ->native(false),
                SelectFilter::make('cashier_session_id')
                    ->relationship('session', 'id')
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
