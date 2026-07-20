<?php

namespace App\Filament\Tenant\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('dashboard.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('primary_phone')
                    ->label(__('dashboard.customer_phone'))
                    ->state(fn ($record) => $record->primaryPhone())
                    ->searchable(),

                TextColumn::make('primary_email')
                    ->label(__('dashboard.customer_email'))
                    ->state(fn ($record) => $record->primaryEmail())
                    ->searchable(),

                TextColumn::make('orders_count')
                    ->label(__('dashboard.orders'))
                    ->counts('orders')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label(__('dashboard.linked_user'))
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()
                    ->visible(fn ($record) => $record->contacts()->exists()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('dashboard.no_customers'))
            ->emptyStateDescription(__('dashboard.no_customers_desc'))
            ->emptyStateActions([]);
    }
}
