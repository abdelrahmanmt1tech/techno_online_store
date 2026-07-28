<?php

namespace App\Filament\Tenant\Resources\PosRegisters\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PosRegisterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    Select::make('branch_id')
                        ->label(__('erp.fields.branch'))
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    Select::make('warehouse_id')
                        ->label(__('erp.fields.warehouse_id'))
                        ->relationship('warehouse', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('cash_drawer_id')
                        ->label(__('commerce.fields.cash_drawer'))
                        ->relationship('cashDrawer', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    TextInput::make('name')->label(__('erp.fields.name'))->required()->maxLength(255),
                    TextInput::make('code')->label(__('erp.fields.code'))->maxLength(50),
                    TextInput::make('receipt_prefix')->label(__('commerce.fields.receipt_prefix'))->maxLength(20)->default('POS'),
                    Toggle::make('is_active')->label(__('erp.fields.is_active'))->default(true),
                    Textarea::make('notes')->label(__('erp.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
