<?php

namespace App\Filament\Resources\Tenants\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('dashboard.tenant_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('dashboard.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(__('dashboard.phone')),

                TextColumn::make('domains.domain')
                    ->label(__('dashboard.subdomain'))
                    ->listWithLineBreaks()
                    ->limitList(1),

                TextColumn::make('country.name')
                    ->label(__('dashboard.country'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('currency.code')
                    ->label(__('dashboard.currency'))
                    ->toggleable(isToggledHiddenByDefault: true),

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
                EditAction::make()
                    ->visible(fn ($record) => Auth::user()->can('tenants.update')),
            ]);
    }
}
