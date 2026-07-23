<?php

namespace App\Filament\Tenant\Resources\Sales\Schemas;

use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleSourceType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('document_number')->label(__('erp.fields.document_number'))->disabled()->dehydrated(),
                    Select::make('source_type')
                        ->label(__('erp.fields.source_type'))
                        ->options(ErpEnumOptions::options(SaleSourceType::class))
                        ->default(SaleSourceType::Manual->value)
                        ->required()
                        ->live()
                        ->native(false),
                    Select::make('order_id')
                        ->label(__('erp.fields.order'))
                        ->relationship('order', 'id')
                        ->getOptionLabelFromRecordUsing(fn ($record) => '#'.$record->id)
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn ($get) => $get('source_type') === SaleSourceType::Order->value),
                    Select::make('customer_id')
                        ->label(__('erp.fields.customer'))
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),
                    Select::make('branch_id')
                        ->label(__('erp.fields.branch'))
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    DatePicker::make('sale_date')
                        ->label(__('erp.fields.sale_date'))
                        ->required()
                        ->default(now()),
                    TextInput::make('currency_code')
                        ->label(__('erp.fields.currency_code'))
                        ->default('EGP')
                        ->maxLength(3)
                        ->required(),
                    Textarea::make('notes')
                        ->label(__('erp.fields.notes'))
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('erp.sections.items'))
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->relationship()
                        ->schema([
                            Select::make('source_type')
                                ->label(__('erp.fields.source_type'))
                                ->options(ErpEnumOptions::options(SaleItemSourceType::class))
                                ->default(SaleItemSourceType::Inventory->value)
                                ->required()
                                ->live()
                                ->native(false),
                            Select::make('inventory_item_id')
                                ->label(__('erp.fields.inventory_item'))
                                ->relationship('inventoryItem', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn ($get) => $get('source_type') === SaleItemSourceType::Inventory->value)
                                ->required(fn ($get) => $get('source_type') === SaleItemSourceType::Inventory->value),
                            Select::make('warehouse_id')
                                ->label(__('erp.fields.warehouse'))
                                ->relationship('warehouse', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn ($get) => $get('source_type') === SaleItemSourceType::Inventory->value)
                                ->required(fn ($get) => $get('source_type') === SaleItemSourceType::Inventory->value),
                            Select::make('product_id')
                                ->label(__('erp.fields.product'))
                                ->relationship('product', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn ($get) => $get('source_type') === SaleItemSourceType::Commerce->value)
                                ->required(fn ($get) => $get('source_type') === SaleItemSourceType::Commerce->value),
                            Select::make('product_variant_id')
                                ->label(__('erp.fields.product_variant'))
                                ->relationship('productVariant', 'sku')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn ($get) => $get('source_type') === SaleItemSourceType::Commerce->value),
                            TextInput::make('description_snapshot')
                                ->label(__('erp.fields.description'))
                                ->maxLength(255)
                                ->visible(fn ($get) => $get('source_type') === SaleItemSourceType::Manual->value)
                                ->required(fn ($get) => $get('source_type') === SaleItemSourceType::Manual->value),
                            Select::make('unit_id')
                                ->label(__('erp.fields.unit'))
                                ->relationship('unit', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false),
                            TextInput::make('quantity')
                                ->label(__('erp.fields.quantity'))
                                ->numeric()
                                ->required()
                                ->minValue(0.0001)
                                ->default(1),
                            TextInput::make('unit_price')
                                ->label(__('erp.fields.unit_price'))
                                ->numeric()
                                ->required()
                                ->default(0),
                            TextInput::make('discount')
                                ->label(__('erp.fields.discount'))
                                ->numeric()
                                ->default(0),
                            TextInput::make('tax')
                                ->label(__('erp.fields.tax'))
                                ->numeric()
                                ->default(0),
                            Textarea::make('notes')
                                ->label(__('erp.fields.notes'))
                                ->rows(1)
                                ->columnSpanFull(),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel(__('erp.actions.add_item'))
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
