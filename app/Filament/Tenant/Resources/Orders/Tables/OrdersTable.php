<?php

namespace App\Filament\Tenant\Resources\Orders\Tables;

use Filament\Actions\ViewAction;
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

                TextColumn::make('status')
                    ->label(__('dashboard.status'))
                    ->badge()
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'confirmed',
                        'primary' => 'processing',
                        'warning' => 'shipped',
                        'success' => 'delivered',
                        'danger' => 'cancelled',
                        'danger' => 'returned',
                    ]),

                TextColumn::make('governorate_name')
                    ->label(__('dashboard.governorate'))
                    ->sortable(),

                TextColumn::make('total')
                    ->label(__('dashboard.total'))
                    ->sortable(),

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
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading(__('dashboard.no_orders'))
            ->emptyStateDescription(__('dashboard.no_orders_desc'))
            ->emptyStateActions([]);
    }
}
