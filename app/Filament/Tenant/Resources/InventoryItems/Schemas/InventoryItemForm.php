<?php

namespace App\Filament\Tenant\Resources\InventoryItems\Schemas;

use App\Enums\Erp\CostingMethod;
use App\Enums\Erp\InventoryItemType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('erp.fields.name'))->required()->maxLength(255),
                    TextInput::make('sku')->label(__('erp.fields.sku'))->maxLength(100),
                    TextInput::make('barcode')->label(__('erp.fields.barcode'))->maxLength(100),
                    Select::make('item_type')
                        ->label(__('erp.fields.item_type'))
                        ->options(ErpEnumOptions::options(InventoryItemType::class))
                        ->default(InventoryItemType::FinishedGood->value)
                        ->required()
                        ->native(false),
                    Select::make('unit_id')
                        ->label(__('erp.fields.unit'))
                        ->relationship('unit', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),
                    Select::make('costing_method')
                        ->label(__('erp.fields.costing_method'))
                        ->options(ErpEnumOptions::options(CostingMethod::class))
                        ->default(CostingMethod::Fifo->value)
                        ->required()
                        ->native(false),
                    Toggle::make('track_stock')->label(__('erp.fields.track_stock'))->default(true),
                    TextInput::make('default_purchase_cost')->label(__('erp.fields.default_purchase_cost'))->numeric()->default(0),
                    TextInput::make('default_sale_price')->label(__('erp.fields.default_sale_price'))->numeric()->default(0),
                    TextInput::make('minimum_stock')->label(__('erp.fields.minimum_stock'))->numeric()->default(0),
                    Toggle::make('is_active')->label(__('erp.fields.is_active'))->default(true),
                    Textarea::make('description')->label(__('erp.fields.description'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('erp.sections.commerce_link'))
                ->columns(2)
                ->schema([
                    Select::make('product_id')
                        ->label(__('erp.fields.product'))
                        ->options(fn () => Product::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->dehydrated()
                        ->visibleOn('create'),
                    Select::make('product_variant_id')
                        ->label(__('erp.fields.product_variant'))
                        ->options(fn () => ProductVariant::query()->orderBy('sku')->pluck('sku', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->dehydrated()
                        ->visibleOn('create'),
                ])
                ->columnSpanFull()
                ->visibleOn('create'),
        ]);
    }
}
