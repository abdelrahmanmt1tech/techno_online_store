<?php

namespace App\Filament\Tenant\Resources\Warehouses\Schemas;

use App\Enums\Erp\WarehouseType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
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
                    TextInput::make('name')->label(__('erp.fields.name'))->required()->maxLength(255),
                    TextInput::make('code')->label(__('erp.fields.code'))->maxLength(50),
                    Select::make('warehouse_type')
                        ->label(__('erp.fields.warehouse_type'))
                        ->options(ErpEnumOptions::options(WarehouseType::class))
                        ->default(WarehouseType::Regular->value)
                        ->required()
                        ->native(false),
                    Toggle::make('is_active')->label(__('erp.fields.is_active'))->default(true),
                    Textarea::make('address')->label(__('erp.fields.address'))->rows(2)->columnSpanFull(),
                    Textarea::make('notes')->label(__('erp.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
