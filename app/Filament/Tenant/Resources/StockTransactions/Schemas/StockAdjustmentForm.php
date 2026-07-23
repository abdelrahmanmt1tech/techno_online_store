<?php

namespace App\Filament\Tenant\Resources\StockTransactions\Schemas;

use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('document_number')
                        ->label(__('erp.fields.document_number'))
                        ->disabled()
                        ->dehydrated(),
                    Select::make('transaction_type')
                        ->label(__('erp.fields.transaction_type'))
                        ->options(ErpEnumOptions::fromCases([
                            StockTransactionType::AdjustmentIn,
                            StockTransactionType::AdjustmentOut,
                        ]))
                        ->default(StockTransactionType::AdjustmentIn->value)
                        ->required()
                        ->live()
                        ->native(false),
                    Select::make('branch_id')
                        ->label(__('erp.fields.branch'))
                        ->relationship('branch', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    DatePicker::make('transaction_date')
                        ->label(__('erp.fields.transaction_date'))
                        ->required()
                        ->default(now()),
                    Select::make('source_warehouse_id')
                        ->label(__('erp.fields.source_warehouse'))
                        ->relationship('sourceWarehouse', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn ($get) => $get('transaction_type') === StockTransactionType::AdjustmentOut->value)
                        ->required(fn ($get) => $get('transaction_type') === StockTransactionType::AdjustmentOut->value),
                    Select::make('destination_warehouse_id')
                        ->label(__('erp.fields.destination_warehouse'))
                        ->relationship('destinationWarehouse', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn ($get) => $get('transaction_type') !== StockTransactionType::AdjustmentOut->value)
                        ->required(fn ($get) => $get('transaction_type') !== StockTransactionType::AdjustmentOut->value),
                    Textarea::make('notes')
                        ->label(__('erp.fields.notes'))
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('erp.sections.lines'))
                ->schema([
                    Repeater::make('lines')
                        ->label('')
                        ->relationship()
                        ->schema([
                            Select::make('source_kind')
                                ->label(__('erp.fields.source_kind'))
                                ->options(ErpEnumOptions::options(StockLineSourceKind::class))
                                ->default(StockLineSourceKind::Inventory->value)
                                ->required()
                                ->live()
                                ->native(false),
                            Select::make('inventory_item_id')
                                ->label(__('erp.fields.inventory_item'))
                                ->relationship('inventoryItem', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn ($get) => $get('source_kind') !== StockLineSourceKind::Commerce->value),
                            Select::make('product_id')
                                ->label(__('erp.fields.product'))
                                ->relationship('product', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn ($get) => $get('source_kind') === StockLineSourceKind::Commerce->value),
                            Select::make('product_variant_id')
                                ->label(__('erp.fields.product_variant'))
                                ->relationship('productVariant', 'sku')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->visible(fn ($get) => $get('source_kind') === StockLineSourceKind::Commerce->value),
                            TextInput::make('quantity')
                                ->label(__('erp.fields.quantity'))
                                ->numeric()
                                ->required()
                                ->minValue(0.0001),
                            TextInput::make('unit_cost')
                                ->label(__('erp.fields.unit_cost'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->visible(fn ($get) => $get('../../transaction_type') === StockTransactionType::AdjustmentIn->value),
                            Toggle::make('affects_commerce_quantity')
                                ->label(__('erp.fields.affects_commerce_quantity'))
                                ->default(false),
                        ])
                        ->columns(3)
                        ->defaultItems(1)
                        ->addActionLabel(__('erp.actions.add_line'))
                        ->collapsible()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
