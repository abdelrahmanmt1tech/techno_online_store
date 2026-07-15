<?php

namespace App\Filament\Tenant\Resources\Products\Tables;

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

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('media')
                    ->label(__('dashboard.image'))
                    ->getStateUsing(fn ($record) => $record->media->first()?->file)
                    ->circular()
                    ->square()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                TextColumn::make('name')
                    ->label(__('dashboard.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('dashboard.type'))
                    ->badge()
                    ->colors([
                        'success' => 'physical',
                        'warning' => 'digital',
                    ]),

                TextColumn::make('price')
                    ->label(__('dashboard.price'))
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label(__('dashboard.quantity'))
                    ->sortable(),

                TextColumn::make('sku')
                    ->label(__('dashboard.sku'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('dashboard.type'))
                    ->options([
                        'physical' => __('dashboard.physical'),
                        'digital' => __('dashboard.digital'),
                    ])
                    ->native(false),

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
            ]);
    }
}
