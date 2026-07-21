<?php

namespace App\Filament\Tenant\Resources\Orders\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('dashboard.order_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label(__('dashboard.customer_name'))
                    ->searchable(),

                SelectColumn::make('status')
                    ->label(__('dashboard.status'))
                    ->options([
                        'pending' => __('dashboard.pending'),
                        'confirmed' => __('dashboard.confirmed'),
                        'processing' => __('dashboard.processing'),
                        'shipped' => __('dashboard.shipped'),
                        'delivered' => __('dashboard.delivered'),
                        'cancelled' => __('dashboard.cancelled'),
                        'returned' => __('dashboard.returned'),
                    ])
                    ->selectablePlaceholder(false),

                TextColumn::make('governorate_name')
                    ->label(__('dashboard.governorate'))
                    ->sortable(),

                TextColumn::make('total')
                    ->label(__('dashboard.total'))
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('dashboard.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('dashboard.'.$state))
                    ->color(fn (string $state): string => match ($state) {
                        'cash' => 'warning',
                        'online' => 'success',
                        default => 'gray',
                    }),

                SelectColumn::make('payment_status')
                    ->label(__('dashboard.payment_status'))
                    ->options([
                        'paid' => __('dashboard.paid'),
                        'unpaid' => __('dashboard.unpaid'),
                    ])
                    ->selectablePlaceholder(false),

                TextColumn::make('discount')
                    ->label(__('dashboard.discount'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('coupon_code')
                    ->label(__('dashboard.coupon'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('dashboard.status'))
                    ->options([
                        'pending' => __('dashboard.pending'),
                        'confirmed' => __('dashboard.confirmed'),
                        'processing' => __('dashboard.processing'),
                        'shipped' => __('dashboard.shipped'),
                        'delivered' => __('dashboard.delivered'),
                        'cancelled' => __('dashboard.cancelled'),
                        'returned' => __('dashboard.returned'),
                    ])
                    ->native(false),

                SelectFilter::make('payment_status')
                    ->label(__('dashboard.payment_status'))
                    ->options([
                        'paid' => __('dashboard.paid'),
                        'unpaid' => __('dashboard.unpaid'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading(__('dashboard.no_orders'))
            ->emptyStateDescription(__('dashboard.no_orders_desc'))
            ->emptyStateActions([]);
    }
}
