<?php

namespace App\Filament\Tenant\Resources\Branches\Tables;

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

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('erp.fields.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('erp.fields.code'))->searchable(),
                TextColumn::make('city')->label(__('erp.fields.city'))->toggleable(),
                TextColumn::make('phone')->label(__('erp.fields.phone'))->toggleable(),
                IconColumn::make('is_main')->label(__('erp.fields.is_main'))->boolean(),
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
                SelectFilter::make('is_main')
                    ->label(__('erp.fields.is_main'))
                    ->options([
                        '1' => __('dashboard.yes'),
                        '0' => __('dashboard.no'),
                    ])
                    ->native(false),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                EditAction::make()
                    ->visible(fn () => Auth::user()->can('erp.branches.manage')),
                DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('erp.branches.manage')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
