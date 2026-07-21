<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('country_code')
                    ->label(__('dashboard.country_code'))
                    ->sortable(),


                TextColumn::make('currency_name.en')
                    ->label(__('dashboard.currency_name'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('currency_symbol')
                    ->label(__('dashboard.currency_symbol')),

                TextColumn::make('phone_code')
                    ->label(__('dashboard.phone_code'))
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('icon')
                    ->label(__('dashboard.icon'))
                    ->disk('public')
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

                TextColumn::make('sort_order')
                    ->label(__('dashboard.sort_order'))
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
            ->emptyStateHeading(__('dashboard.no_countries'))
            ->emptyStateDescription(__('dashboard.no_countries_desc'))
            ->emptyStateActions([]);
    }
}
