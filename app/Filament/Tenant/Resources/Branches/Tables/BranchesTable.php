<?php

namespace App\Filament\Tenant\Resources\Branches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

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
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('erp.empty.default'));
    }
}
