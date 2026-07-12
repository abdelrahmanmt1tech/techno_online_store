<?php

namespace App\Filament\Resources\Faqs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereNull('faqable_type')->whereNull('faqable_id'))
            ->defaultSort('order')
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('question')
                    ->label(__('dashboard.question'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('answer')
                    ->label(__('dashboard.answer'))
                    ->limit(50)
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label(__('dashboard.active')),

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
