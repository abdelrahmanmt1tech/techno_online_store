<?php

namespace App\Filament\Tenant\Resources\InvoicePrintSettings\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoicePrintSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('erp.print_settings.company'))
                ->columns(2)
                ->schema([
                    TextInput::make('company_name.ar')->label(__('erp.print_settings.company_name_ar'))->required(),
                    TextInput::make('company_name.en')->label(__('erp.print_settings.company_name_en'))->required(),
                    TextInput::make('legal_name.ar')->label(__('erp.print_settings.legal_name_ar')),
                    TextInput::make('legal_name.en')->label(__('erp.print_settings.legal_name_en')),
                    Textarea::make('address.ar')->label(__('erp.print_settings.address_ar'))->rows(2),
                    Textarea::make('address.en')->label(__('erp.print_settings.address_en'))->rows(2),
                    TextInput::make('city')->label(__('erp.fields.city')),
                    TextInput::make('country')->label(__('erp.fields.country')),
                    TextInput::make('postal_code')->label(__('erp.print_settings.postal_code')),
                    TextInput::make('phone')->label(__('erp.fields.phone'))->tel(),
                    TextInput::make('email')->label(__('erp.fields.email'))->email(),
                    TextInput::make('website')->label(__('erp.print_settings.website'))->url()->nullable(),
                ]),

            Section::make(__('erp.print_settings.legal'))
                ->columns(2)
                ->schema([
                    TextInput::make('tax_number')->label(__('erp.print.tax_number')),
                    TextInput::make('commercial_register')->label(__('erp.print.commercial_register')),
                    TextInput::make('extra_registration')->label(__('erp.print_settings.extra_registration'))->columnSpanFull(),
                ]),

            Section::make(__('erp.print_settings.branding'))
                ->columns(3)
                ->schema([
                    FileUpload::make('logo_path')
                        ->label(__('erp.print_settings.logo'))
                        ->image()
                        ->directory('invoice-print')
                        ->maxSize(2048)
                        ->imagePreviewHeight('80'),
                    FileUpload::make('stamp_path')
                        ->label(__('erp.print_settings.stamp'))
                        ->image()
                        ->directory('invoice-print')
                        ->maxSize(2048)
                        ->imagePreviewHeight('80'),
                    FileUpload::make('signature_path')
                        ->label(__('erp.print_settings.signature'))
                        ->image()
                        ->directory('invoice-print')
                        ->maxSize(2048)
                        ->imagePreviewHeight('80'),
                ]),

            Section::make(__('erp.print_settings.design'))
                ->columns(3)
                ->schema([
                    ColorPicker::make('primary_color')->label(__('erp.print_settings.primary_color')),
                    TextInput::make('logo_width')->label(__('erp.print_settings.logo_width'))->numeric()->minValue(60)->maxValue(240),
                    Select::make('logo_align')
                        ->label(__('erp.print_settings.logo_align'))
                        ->options([
                            'start' => __('erp.print_settings.align_start'),
                            'center' => __('erp.print_settings.align_center'),
                            'end' => __('erp.print_settings.align_end'),
                        ])
                        ->native(false),
                    Select::make('paper_size')
                        ->label(__('erp.print_settings.paper_size'))
                        ->options(['A4' => 'A4', 'A5' => 'A5'])
                        ->native(false),
                    Select::make('paper_orientation')
                        ->label(__('erp.print_settings.paper_orientation'))
                        ->options([
                            'portrait' => __('erp.print_settings.portrait'),
                            'landscape' => __('erp.print_settings.landscape'),
                        ])
                        ->native(false),
                    Select::make('default_locale')
                        ->label(__('erp.print_settings.default_locale'))
                        ->options([
                            'auto' => __('erp.print_settings.locale_auto'),
                            'ar' => __('erp.print_settings.locale_ar'),
                            'en' => __('erp.print_settings.locale_en'),
                        ])
                        ->native(false),
                ]),

            Section::make(__('erp.print_settings.sales_texts'))
                ->columns(2)
                ->schema([
                    TextInput::make('sales_invoice_title.ar')->label(__('erp.print_settings.title_ar')),
                    TextInput::make('sales_invoice_title.en')->label(__('erp.print_settings.title_en')),
                    Textarea::make('header_text.ar')->label(__('erp.print_settings.header_ar'))->rows(2),
                    Textarea::make('header_text.en')->label(__('erp.print_settings.header_en'))->rows(2),
                    Textarea::make('closing_note.ar')->label(__('erp.print_settings.closing_ar'))->rows(2),
                    Textarea::make('closing_note.en')->label(__('erp.print_settings.closing_en'))->rows(2),
                ]),

            Section::make(__('erp.print_settings.purchase_texts'))
                ->columns(2)
                ->schema([
                    TextInput::make('purchase_invoice_title.ar')->label(__('erp.print_settings.title_ar')),
                    TextInput::make('purchase_invoice_title.en')->label(__('erp.print_settings.title_en')),
                ]),

            Section::make(__('erp.print_settings.terms_footer'))
                ->columns(2)
                ->schema([
                    Textarea::make('terms.ar')->label(__('erp.print_settings.terms_ar'))->rows(3),
                    Textarea::make('terms.en')->label(__('erp.print_settings.terms_en'))->rows(3),
                    Textarea::make('footer_text.ar')->label(__('erp.print_settings.footer_ar'))->rows(2),
                    Textarea::make('footer_text.en')->label(__('erp.print_settings.footer_en'))->rows(2),
                    TextInput::make('authority_name.ar')->label(__('erp.print_settings.authority_ar')),
                    TextInput::make('authority_name.en')->label(__('erp.print_settings.authority_en')),
                    TextInput::make('signature_label.ar')->label(__('erp.print_settings.signature_label_ar')),
                    TextInput::make('signature_label.en')->label(__('erp.print_settings.signature_label_en')),
                    TextInput::make('stamp_label.ar')->label(__('erp.print_settings.stamp_label_ar')),
                    TextInput::make('stamp_label.en')->label(__('erp.print_settings.stamp_label_en')),
                ]),

            Section::make(__('erp.print_settings.display_options'))
                ->schema([
                    Grid::make(3)->schema(self::displayToggles()),
                ]),
        ]);
    }

    /**
     * @return list<Toggle>
     */
    private static function displayToggles(): array
    {
        $keys = [
            'show_logo', 'show_legal_name', 'show_address', 'show_phone', 'show_email', 'show_website',
            'show_tax_number', 'show_commercial_register',
            'show_status', 'show_invoice_date', 'show_due_date', 'show_sale_number', 'show_order_id',
            'show_purchase_order', 'show_goods_receipt', 'show_branch', 'show_created_by',
            'show_customer_name', 'show_customer_phone', 'show_customer_email', 'show_customer_address',
            'show_supplier_name', 'show_supplier_phone', 'show_supplier_email', 'show_supplier_address',
            'show_party_tax_number',
            'show_sku', 'show_description', 'show_variation', 'show_unit', 'show_quantity',
            'show_unit_price', 'show_discount', 'show_tax', 'show_line_total',
            'show_subtotal', 'show_discount_total', 'show_tax_total', 'show_grand_total',
            'show_paid_amount', 'show_due_amount', 'show_payment_status',
            'show_notes', 'show_terms', 'show_closing_note', 'show_signature', 'show_stamp',
        ];

        return array_map(
            fn (string $key) => Toggle::make('display_options.'.$key)
                ->label(__('erp.print_display.'.$key))
                ->inline(false),
            $keys,
        );
    }
}
