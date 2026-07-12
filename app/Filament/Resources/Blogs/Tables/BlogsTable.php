<?php

namespace App\Filament\Resources\Blogs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('dashboard.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('categories.name')
                    ->label(__('dashboard.blog_categories'))
                    ->badge(),

                TextColumn::make('tags.name')
                    ->label(__('dashboard.blog_tags'))
                    ->badge(),

                TextColumn::make('views_count')
                    ->label(__('dashboard.views_count'))
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

                ToggleColumn::make('is_featured')
                    ->label(__('dashboard.featured')),

                TextColumn::make('published_at')
                    ->label(__('dashboard.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('dashboard.status'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
