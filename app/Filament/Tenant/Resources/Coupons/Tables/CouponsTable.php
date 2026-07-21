<?php

namespace App\Filament\Tenant\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label(__('dashboard.coupon_code'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label(__('dashboard.coupon_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'percentage' => __('dashboard.percentage'),
                        'fixed' => __('dashboard.fixed_amount'),
                        default => $state,
                    })
                    ->colors([
                        'info' => 'percentage',
                        'success' => 'fixed',
                    ]),

                TextColumn::make('value')
                    ->label(__('dashboard.coupon_value'))
                    ->formatStateUsing(fn ($record) => $record->type === 'percentage'
                        ? $record->value.'%'
                        : number_format($record->value, 2))
                    ->sortable(),

                TextColumn::make('minimum_order_amount')
                    ->label(__('dashboard.minimum_order_amount'))
                    ->sortable(),

                TextColumn::make('usage_count')
                    ->label(__('dashboard.usage_count'))
                    ->formatStateUsing(fn ($record) => $record->usage_count.($record->usage_limit ? " / {$record->usage_limit}" : ''))
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

                TextColumn::make('expires_at')
                    ->label(__('dashboard.expires_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('dashboard.active'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),

                SelectFilter::make('type')
                    ->label(__('dashboard.coupon_type'))
                    ->options([
                        'percentage' => __('dashboard.percentage'),
                        'fixed' => __('dashboard.fixed_amount'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('dashboard.no_coupons'))
            ->emptyStateDescription(__('dashboard.no_coupons_desc'))
            ->emptyStateActions([]);
    }
}
