<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

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

                // IconColumn::make('show_in_header')
                //     ->label(__('dashboard.show_in_header'))
                //     ->boolean(),

                // IconColumn::make('show_in_footer')
                //     ->label(__('dashboard.show_in_footer'))
                //     ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('dashboard.page_sort_order'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('dashboard.status'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),
                SelectFilter::make('show_in_header')
                    ->label(__('dashboard.show_in_header'))
                    ->options([
                        '1' => __('dashboard.yes'),
                        '0' => __('dashboard.no'),
                    ])
                    ->native(false),
                SelectFilter::make('show_in_footer')
                    ->label(__('dashboard.show_in_footer'))
                    ->options([
                        '1' => __('dashboard.yes'),
                        '0' => __('dashboard.no'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => Auth::user()->can('pages.view')),
                EditAction::make()
                    ->visible(fn () => Auth::user()->can('pages.update')),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('pages.delete'))
                    ->hidden(fn (Page $record) => $record->isProtected()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('dashboard.delete_selected'))
                        ->action(function (Collection $records) {
                            $records->reject(fn (Page $record) => $record->isProtected())
                                ->each(fn (Page $record) => $record->delete());
                        }),
                ]),
            ]);
    }
}
