<?php

namespace App\Filament\Tenant\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('erp.fields.name'))->searchable()->sortable(),
                TextColumn::make('code')->label(__('erp.fields.code'))->searchable(),
                TextColumn::make('phone')->label(__('erp.fields.phone'))->toggleable(),
                TextColumn::make('email')->label(__('erp.fields.email'))->toggleable(),
                TextColumn::make('payment_terms_days')->label(__('erp.fields.payment_terms_days'))->toggleable(),
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
