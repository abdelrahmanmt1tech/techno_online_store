<?php

namespace App\Filament\Tenant\Resources\Reviews\Tables;

use App\Filament\Tenant\Resources\Products\ProductResource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('dashboard.reviews_user'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reviewable.name')
                    ->label(__('dashboard.product'))
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => ProductResource::getUrl('view', ['record' => $record->reviewable_id]))
                    ->openUrlInNewTab()
                    ->color('primary'),

                TextColumn::make('rating')
                    ->label(__('dashboard.reviews_rating'))
                    ->icon('heroicon-o-star')
                    ->iconColor('warning')
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('dashboard.reviews_title'))
                    ->searchable()
                    ->limit(50),

                TextColumn::make('comment')
                    ->label(__('dashboard.reviews_comment'))
                    ->limit(80)
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_approved')
                    ->label(__('dashboard.reviews_approved')),

                ToggleColumn::make('is_featured')
                    ->label(__('dashboard.reviews_featured')),

                TextColumn::make('created_at')
                    ->label(__('dashboard.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label(__('dashboard.reviews_approved'))
                    ->options([
                        1 => __('dashboard.yes'),
                        0 => __('dashboard.no'),
                    ])
                    ->native(false),

                SelectFilter::make('is_featured')
                    ->label(__('dashboard.reviews_featured'))
                    ->options([
                        1 => __('dashboard.yes'),
                        0 => __('dashboard.no'),
                    ])
                    ->native(false),

                SelectFilter::make('rating')
                    ->label(__('dashboard.reviews_rating'))
                    ->options([
                        5 => '5',
                        4 => '4',
                        3 => '3',
                        2 => '2',
                        1 => '1',
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->emptyStateHeading(__('dashboard.no_reviews'));
    }
}
