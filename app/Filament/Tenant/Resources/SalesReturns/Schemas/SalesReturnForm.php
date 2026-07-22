<?php

namespace App\Filament\Tenant\Resources\SalesReturns\Schemas;

use App\Enums\Erp\ReturnDisposition;
use App\Enums\Erp\SaleItemSourceType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('document_number')->label(__('erp.fields.document_number'))->disabled()->dehydrated(),
                    Select::make('sale_id')->label(__('erp.fields.sale'))->relationship('sale', 'document_number')->searchable()->preload()->required()->native(false),
                    Select::make('sales_invoice_id')->label(__('erp.fields.sales_invoice'))->relationship('salesInvoice', 'document_number')->searchable()->preload()->native(false),
                    Select::make('branch_id')->label(__('erp.fields.branch'))->relationship('branch', 'name')->searchable()->preload()->required()->native(false),
                    DatePicker::make('return_date')->label(__('erp.fields.return_date'))->required()->default(now()),
                    Textarea::make('reason')->label(__('erp.fields.reason'))->rows(2)->required()->columnSpanFull(),
                    Textarea::make('notes')->label(__('erp.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('erp.sections.items'))
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('sale_item_id')->label(__('erp.fields.sale_item'))->relationship('saleItem', 'id')
                                ->getOptionLabelFromRecordUsing(fn ($record) => '#'.$record->id.' — '.($record->description_snapshot ?: $record->inventory_item_id))
                                ->searchable()->preload()->required()->native(false),
                            Select::make('source_type')->label(__('erp.fields.source_type'))->options(ErpEnumOptions::options(SaleItemSourceType::class))->default(SaleItemSourceType::Inventory->value)->required()->native(false),
                            Select::make('disposition')->label(__('erp.fields.disposition'))->options(ErpEnumOptions::options(ReturnDisposition::class))->default(ReturnDisposition::Restock->value)->required()->native(false),
                            Select::make('warehouse_id')->label(__('erp.fields.warehouse'))->relationship('warehouse', 'name')->searchable()->preload()->native(false),
                            TextInput::make('quantity')->label(__('erp.fields.quantity'))->numeric()->required()->default(1),
                            TextInput::make('unit_price')->label(__('erp.fields.unit_price'))->numeric()->default(0),
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
