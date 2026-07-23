<?php

namespace App\Filament\Tenant\Resources\PurchaseReturns\Schemas;

use App\Enums\Erp\PurchaseLineType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PurchaseReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('document_number')->label(__('erp.fields.document_number'))->disabled()->dehydrated(),
                    Select::make('supplier_id')->label(__('erp.fields.supplier'))->relationship('supplier', 'name')->searchable()->preload()->required()->native(false),
                    Select::make('purchase_invoice_id')->label(__('erp.fields.purchase_invoice'))->relationship('purchaseInvoice', 'document_number')->searchable()->preload()->native(false),
                    Select::make('goods_receipt_id')->label(__('erp.fields.goods_receipt'))->relationship('goodsReceipt', 'document_number')->searchable()->preload()->native(false),
                    Select::make('branch_id')->label(__('erp.fields.branch'))->relationship('branch', 'name')->searchable()->preload()->required()->native(false),
                    Select::make('warehouse_id')->label(__('erp.fields.warehouse'))->relationship('warehouse', 'name')->searchable()->preload()->required()->native(false),
                    DatePicker::make('return_date')->label(__('erp.fields.return_date'))->required()->default(now()),
                    Textarea::make('reason')->label(__('erp.fields.reason'))->rows(2)->columnSpanFull(),
                    Textarea::make('notes')->label(__('erp.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('erp.sections.items'))
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('line_type')->label(__('erp.fields.line_type'))->options(ErpEnumOptions::options(PurchaseLineType::class))->default(PurchaseLineType::Inventory->value)->required()->live()->native(false),
                            Select::make('inventory_item_id')->label(__('erp.fields.inventory_item'))->relationship('inventoryItem', 'name')->searchable()->preload()->native(false)
                                ->visible(fn ($get) => $get('line_type') !== PurchaseLineType::Commerce->value),
                            Select::make('product_id')->label(__('erp.fields.product'))->relationship('product', 'name')->searchable()->preload()->native(false)
                                ->visible(fn ($get) => $get('line_type') === PurchaseLineType::Commerce->value),
                            Select::make('product_variant_id')->label(__('erp.fields.product_variant'))->relationship('productVariant', 'sku')->searchable()->preload()->native(false)
                                ->visible(fn ($get) => $get('line_type') === PurchaseLineType::Commerce->value),
                            TextInput::make('description_snapshot')->label(__('erp.fields.description'))->maxLength(255),
                            TextInput::make('quantity')->label(__('erp.fields.quantity'))->numeric()->required()->default(1),
                            TextInput::make('unit_cost')->label(__('erp.fields.unit_cost'))->numeric()->default(0),
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
