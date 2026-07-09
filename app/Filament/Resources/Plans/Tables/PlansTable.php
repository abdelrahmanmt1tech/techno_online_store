<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('dashboard.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('dashboard.type'))
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => __("dashboard.$state"))
                    ->color(fn ($state) => $state === 'commission' ? 'success' : 'info'),

                TextColumn::make('price')
                    ->label(__('dashboard.price'))
                    ->money(fn ($record) => $record->currency ?? 'SAR')
                    ->sortable(),

                TextColumn::make('commission_per_order')
                    ->label(__('dashboard.commission_per_order'))
                    ->money(fn ($record) => $record->currency ?? 'SAR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('subscription_period')
                    ->label(__('dashboard.subscription_period'))
                    ->badge()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => __("dashboard.$state"))
                    ->color(fn ($state) => $state === 'monthly' ? 'warning' : 'info'),

                TextColumn::make('features_count')
                    ->label(__('dashboard.features'))
                    ->counts('features')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('dashboard.type'))
                    ->options([
                        'commission' => __('dashboard.commission'),
                        'subscription' => __('dashboard.subscription'),
                    ]),

                SelectFilter::make('is_active')
                    ->label(__('dashboard.status'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record) => Auth::user()->can('plans.update')),
                DeleteAction::make()
                    ->visible(fn ($record) => Auth::user()->can('plans.delete')),
            ]);
    }
}
