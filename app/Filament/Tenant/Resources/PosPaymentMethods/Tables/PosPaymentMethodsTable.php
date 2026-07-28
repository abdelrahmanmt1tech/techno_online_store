<?php

namespace App\Filament\Tenant\Resources\PosPaymentMethods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PosPaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label(__('erp.fields.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('erp.fields.code'))->searchable(),
                TextColumn::make('type')
                    ->label(__('commerce.fields.type'))
                    ->formatStateUsing(fn (?string $state) => $state ? __('commerce.pos_payment_types.'.$state) : null)
                    ->badge(),
                IconColumn::make('opens_cash_drawer')->label(__('commerce.fields.opens_cash_drawer'))->boolean(),
                TextColumn::make('sort_order')->label(__('commerce.fields.sort_order'))->sortable(),
                ToggleColumn::make('is_active')->label(__('erp.fields.is_active')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('commerce.fields.type'))
                    ->options([
                        'cash' => __('commerce.pos_payment_types.cash'),
                        'card' => __('commerce.pos_payment_types.card'),
                        'transfer' => __('commerce.pos_payment_types.transfer'),
                        'other' => __('commerce.pos_payment_types.other'),
                    ])
                    ->native(false),
                SelectFilter::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
