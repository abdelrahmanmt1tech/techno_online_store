<?php

namespace App\Filament\Resources\Themes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ThemesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                ImageColumn::make('image')
                    ->label(__('dashboard.image'))
                    ->disk('public')
                    ->circular(),

                TextColumn::make('name')
                    ->label(__('dashboard.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('dashboard.slug'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('price')
                    ->label(__('dashboard.price'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('categories_count')
                    ->label(__('dashboard.categories'))
                    ->counts('categories')
                    ->sortable(),

                TextColumn::make('downloads_count')
                    ->label(__('dashboard.downloads_count'))
                    ->sortable()
                    ->toggleable(),

                ToggleColumn::make('featured')
                    ->label(__('dashboard.featured')),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_free')
                    ->label(__('dashboard.pricing'))
                    ->options([
                        '1' => __('dashboard.free'),
                        '0' => __('dashboard.paid'),
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
                    ->visible(fn ($record) => Auth::user()->can('themes.update')),
                DeleteAction::make()
                    ->visible(fn ($record) => Auth::user()->can('themes.delete')),
            ]);
    }
}
