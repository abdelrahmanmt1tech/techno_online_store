<?php

namespace App\Filament\Tenant\Resources\UnitsOfMeasure\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UnitsOfMeasureTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('erp.fields.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('erp.fields.code'))->searchable(),
                TextColumn::make('symbol')->label(__('erp.fields.symbol')),
                IconColumn::make('allows_decimal')->label(__('erp.fields.allows_decimal'))->boolean(),
                TextColumn::make('precision')->label(__('erp.fields.precision')),
                ToggleColumn::make('is_active')->label(__('erp.fields.is_active')),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label(__('erp.fields.is_active'))
                    ->options([
                        '1' => __('dashboard.active'),
                        '0' => __('dashboard.inactive'),
                    ])
                    ->native(false),
                SelectFilter::make('allows_decimal')
                    ->label(__('erp.fields.allows_decimal'))
                    ->options([
                        '1' => __('dashboard.yes'),
                        '0' => __('dashboard.no'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()->can('erp.uom.manage')),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('erp.uom.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
