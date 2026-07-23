<?php

namespace App\Filament\Tenant\Resources\Pages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')
                    ->label(__('dashboard.page_image'))
                    ->size(60),

                TextColumn::make('title')
                    ->label(__('dashboard.page_title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('dashboard.page_slug'))
                    ->searchable()
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.page_active')),

                IconColumn::make('show_in_header')
                    ->label(__('dashboard.show_in_header'))
                    ->boolean(),

                IconColumn::make('show_in_footer')
                    ->label(__('dashboard.show_in_footer'))
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('dashboard.page_sort_order'))
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('dashboard.delete_selected')),
                ]),
            ]);
    }
}
