<?php

namespace App\Filament\Tenant\Resources\SalesInvoices\Schemas;

use App\Enums\Erp\SaleItemSourceType;
use App\Filament\Tenant\Support\Erp\ErpEnumOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SalesInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.sections.details'))
                ->columns(3)
                ->schema([
                    TextInput::make('document_number')->label(__('erp.fields.document_number'))->disabled()->dehydrated(),
                    Select::make('sale_id')->label(__('erp.fields.sale'))->relationship('sale', 'document_number')->searchable()->preload()->native(false),
                    Select::make('customer_id')->label(__('erp.fields.customer'))->relationship('customer', 'name')->searchable()->preload()->native(false),
                    Select::make('branch_id')->label(__('erp.fields.branch'))->relationship('branch', 'name')->searchable()->preload()->required()->native(false),
                    DatePicker::make('invoice_date')->label(__('erp.fields.invoice_date'))->required()->default(now()),
                    DatePicker::make('due_date')->label(__('erp.fields.due_date')),
                    TextInput::make('currency_code')->label(__('erp.fields.currency_code'))->default('EGP')->maxLength(3)->required(),
                    TextInput::make('subtotal')->label(__('erp.fields.subtotal'))->numeric()->default(0),
                    TextInput::make('discount_total')->label(__('erp.fields.discount_total'))->numeric()->default(0),
                    TextInput::make('tax_total')->label(__('erp.fields.tax_total'))->numeric()->default(0),
                    TextInput::make('grand_total')->label(__('erp.fields.grand_total'))->numeric()->default(0),
                    Textarea::make('notes')->label(__('erp.fields.notes'))->rows(2)->columnSpanFull(),
                ])
                ->columnSpanFull(),
            Section::make(__('erp.sections.items'))
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->schema([
                            Select::make('source_type')->label(__('erp.fields.source_type'))->options(ErpEnumOptions::options(SaleItemSourceType::class))->default(SaleItemSourceType::Inventory->value)->required()->native(false),
                            TextInput::make('description_snapshot')->label(__('erp.fields.description'))->maxLength(255),
                            TextInput::make('quantity')->label(__('erp.fields.quantity'))->numeric()->required()->default(1),
                            TextInput::make('unit_price')->label(__('erp.fields.unit_price'))->numeric()->required()->default(0),
                            TextInput::make('discount')->label(__('erp.fields.discount'))->numeric()->default(0),
                            TextInput::make('tax')->label(__('erp.fields.tax'))->numeric()->default(0),
                            TextInput::make('line_total')->label(__('erp.fields.line_total'))->numeric()->default(0),
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
