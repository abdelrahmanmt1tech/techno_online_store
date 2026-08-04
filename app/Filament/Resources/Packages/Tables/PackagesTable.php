<?php

namespace App\Filament\Resources\Packages\Tables;

use App\Models\Package;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('image')
                    ->label(__('dashboard.image'))
                    ->disk('public')
                    ->circular(),

                TextColumn::make('name')
                    ->label(__('dashboard.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('module')
                    ->label(__('dashboard.module'))
                    ->badge()
                    ->sortable()
                    ->state(fn (Package $record) => $record->is_full_package
                        ? __('dashboard.full_package')
                        : ($record->module ? __('modules.'.$record->module) : null))
                    ->color(fn (Package $record) => $record->is_full_package ? 'success' : 'info'),

                TextColumn::make('trials_duration')
                    ->label(__('dashboard.trials_duration'))
                    ->sortable(),

                // TextColumn::make('prices_count')
                //     ->label(__('dashboard.package_prices'))
                //     ->counts('prices')
                //     ->sortable(),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('module')
                    ->label(__('dashboard.module'))
                    ->options([
                        'store' => __('modules.store'),
                        'pos' => __('modules.pos'),
                        'crm' => __('modules.crm'),
                        'accounting' => __('modules.accounting'),
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
            ]);
    }
}
