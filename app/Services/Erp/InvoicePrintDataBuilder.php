<?php

namespace App\Services\Erp;

use App\Enums\Erp\InvoiceStatus;
use App\Models\Tenant\PurchaseInvoice;
use App\Models\Tenant\SalesInvoice;
use App\Support\Erp\Decimal;
use App\Support\Erp\TenantMediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * بناء بيانات عرض الفاتورة للطباعة — بلا استعلامات داخل Blade.
 */
final class InvoicePrintDataBuilder
{
    public function __construct(
        private readonly InvoicePrintSettingsService $settingsService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forSalesInvoice(SalesInvoice $invoice, ?string $forceLocale = null): array
    {
        $invoice->loadMissing([
            'items',
            'customer.contacts',
            'branch',
            'sale',
            'order',
            'creator',
        ]);

        $settingsPayload = $this->resolveSettingsPayload($invoice, $forceLocale);
        $locale = $settingsPayload['locale'];
        $display = $settingsPayload['display_options'];

        $customer = $invoice->customer;
        $phone = $customer?->primaryPhone();
        $email = $customer?->primaryEmail();

        return [
            'type' => 'sales',
            'locale' => $locale,
            'direction' => $settingsPayload['direction'],
            'paper_size' => $settingsPayload['paper_size'],
            'paper_orientation' => $settingsPayload['paper_orientation'],
            'primary_color' => $settingsPayload['primary_color'],
            'logo_width' => $settingsPayload['logo_width'],
            'logo_align' => $settingsPayload['logo_align'],
            'display' => $display,
            'watermark' => $this->watermark($invoice->status),
            'title' => $this->t($settingsPayload['sales_invoice_title'] ?? [], $locale, __('erp.print.sales_invoice_title', [], $locale)),
            'document_number' => $invoice->document_number,
            'status' => $invoice->status instanceof InvoiceStatus ? $invoice->status->label() : (string) $invoice->status,
            'status_value' => $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status,
            'invoice_date' => optional($invoice->invoice_date)?->translatedFormat('Y-m-d'),
            'due_date' => optional($invoice->due_date)?->translatedFormat('Y-m-d'),
            'currency_code' => $invoice->currency_code ?: '',
            'company' => $this->companyBlock($settingsPayload, $locale, $display),
            'party' => [
                'label' => __('erp.print.bill_to', [], $locale),
                'name' => $display['show_customer_name'] ? ($customer?->name) : null,
                'phone' => $display['show_customer_phone'] ? $phone : null,
                'email' => $display['show_customer_email'] ? $email : null,
                'address' => $display['show_customer_address'] ? null : null,
                'tax_number' => $display['show_party_tax_number'] ? null : null,
            ],
            'meta' => array_filter([
                'sale_number' => $display['show_sale_number'] ? $invoice->sale?->document_number : null,
                'order_number' => $display['show_order_id'] ? ($invoice->order?->order_number ?: ($invoice->order_id ? '#'.$invoice->order_id : null)) : null,
                'branch_name' => $display['show_branch'] ? $invoice->branch?->name : null,
                'branch_phone' => $display['show_branch'] ? $invoice->branch?->phone : null,
                'branch_address' => $display['show_branch'] ? $invoice->branch?->address : null,
                'created_by' => $display['show_created_by'] ? $invoice->creator?->name : null,
            ], fn ($v) => filled($v)),
            'items' => $invoice->items->map(fn ($item) => [
                'sku' => $display['show_sku'] ? $item->sku_snapshot : null,
                'description' => $display['show_description'] ? $item->description_snapshot : null,
                'variation' => $display['show_variation'] ? $item->variation_snapshot : null,
                'quantity' => $display['show_quantity'] ? $this->qty($item->quantity) : null,
                'unit_price' => $display['show_unit_price'] ? $this->money($item->unit_price) : null,
                'discount' => $display['show_discount'] ? $this->money($item->discount) : null,
                'tax' => $display['show_tax'] ? $this->money($item->tax) : null,
                'line_total' => $display['show_line_total'] ? $this->money($item->line_total) : null,
            ])->all(),
            'totals' => [
                'subtotal' => $display['show_subtotal'] ? $this->money($invoice->subtotal) : null,
                'discount_total' => $display['show_discount_total'] ? $this->money($invoice->discount_total) : null,
                'tax_total' => $display['show_tax_total'] ? $this->money($invoice->tax_total) : null,
                'grand_total' => $display['show_grand_total'] ? $this->money($invoice->grand_total) : null,
                'paid_amount' => $display['show_paid_amount'] ? $this->money($invoice->paid_amount) : null,
                'due_amount' => $display['show_due_amount'] ? $this->money($invoice->due_amount) : null,
                'payment_status' => $display['show_payment_status'] ? $this->paymentStatusLabel($invoice, $locale) : null,
            ],
            'notes' => $display['show_notes'] ? $invoice->notes : null,
            'terms' => $display['show_terms'] ? $this->t($settingsPayload['terms'] ?? [], $locale) : null,
            'closing_note' => $display['show_closing_note'] ? $this->t($settingsPayload['closing_note'] ?? [], $locale) : null,
            'footer_text' => $this->t($settingsPayload['footer_text'] ?? [], $locale),
            'header_text' => $this->t($settingsPayload['header_text'] ?? [], $locale),
            'signature_label' => $display['show_signature'] ? $this->t($settingsPayload['signature_label'] ?? [], $locale) : null,
            'stamp_label' => $display['show_stamp'] ? $this->t($settingsPayload['stamp_label'] ?? [], $locale) : null,
            'authority_name' => $display['show_signature'] ? $this->t($settingsPayload['authority_name'] ?? [], $locale) : null,
            'signature_url' => $display['show_signature'] ? ($settingsPayload['signature_url'] ?? TenantMediaUrl::make($settingsPayload['signature_path'] ?? null)) : null,
            'stamp_url' => $display['show_stamp'] ? ($settingsPayload['stamp_url'] ?? TenantMediaUrl::make($settingsPayload['stamp_path'] ?? null)) : null,
            'column_flags' => [
                'sku' => $display['show_sku'],
                'description' => $display['show_description'],
                'variation' => $display['show_variation'],
                'quantity' => $display['show_quantity'],
                'unit_price' => $display['show_unit_price'],
                'discount' => $display['show_discount'],
                'tax' => $display['show_tax'],
                'line_total' => $display['show_line_total'],
            ],
            'labels' => $this->labels($locale),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function forPurchaseInvoice(PurchaseInvoice $invoice, ?string $forceLocale = null): array
    {
        $invoice->loadMissing([
            'items',
            'supplier',
            'purchaseOrder',
            'goodsReceipt',
            'creator',
        ]);

        // branch عبر PO إن وُجد
        $invoice->purchaseOrder?->loadMissing('branch');

        $settingsPayload = $this->resolveSettingsPayload($invoice, $forceLocale);
        $locale = $settingsPayload['locale'];
        $display = $settingsPayload['display_options'];
        $supplier = $invoice->supplier;
        $branch = $invoice->purchaseOrder?->branch;

        return [
            'type' => 'purchase',
            'locale' => $locale,
            'direction' => $settingsPayload['direction'],
            'paper_size' => $settingsPayload['paper_size'],
            'paper_orientation' => $settingsPayload['paper_orientation'],
            'primary_color' => $settingsPayload['primary_color'],
            'logo_width' => $settingsPayload['logo_width'],
            'logo_align' => $settingsPayload['logo_align'],
            'display' => $display,
            'watermark' => $this->watermark($invoice->status),
            'title' => $this->t($settingsPayload['purchase_invoice_title'] ?? [], $locale, __('erp.print.purchase_invoice_title', [], $locale)),
            'document_number' => $invoice->document_number,
            'status' => $invoice->status instanceof InvoiceStatus ? $invoice->status->label() : (string) $invoice->status,
            'status_value' => $invoice->status instanceof InvoiceStatus ? $invoice->status->value : (string) $invoice->status,
            'invoice_date' => optional($invoice->invoice_date)?->translatedFormat('Y-m-d'),
            'due_date' => optional($invoice->due_date)?->translatedFormat('Y-m-d'),
            'currency_code' => $invoice->currency_code ?: '',
            'company' => $this->companyBlock($settingsPayload, $locale, $display),
            'party' => [
                'label' => __('erp.print.vendor', [], $locale),
                'name' => $display['show_supplier_name'] ? ($supplier?->name) : null,
                'phone' => $display['show_supplier_phone'] ? ($supplier?->phone) : null,
                'email' => $display['show_supplier_email'] ? ($supplier?->email) : null,
                'address' => $display['show_supplier_address'] ? ($supplier?->address) : null,
                'tax_number' => $display['show_party_tax_number'] ? ($supplier?->tax_number) : null,
            ],
            'meta' => array_filter([
                'supplier_invoice_number' => $invoice->supplier_invoice_number,
                'purchase_order' => $display['show_purchase_order'] ? $invoice->purchaseOrder?->document_number : null,
                'goods_receipt' => $display['show_goods_receipt'] ? $invoice->goodsReceipt?->document_number : null,
                'branch_name' => $display['show_branch'] ? $branch?->name : null,
                'branch_phone' => $display['show_branch'] ? $branch?->phone : null,
                'branch_address' => $display['show_branch'] ? $branch?->address : null,
                'created_by' => $display['show_created_by'] ? $invoice->creator?->name : null,
            ], fn ($v) => filled($v)),
            'items' => $invoice->items->map(fn ($item) => [
                'sku' => $display['show_sku'] ? $item->sku_snapshot : null,
                'description' => $display['show_description'] ? $item->description_snapshot : null,
                'variation' => null,
                'quantity' => $display['show_quantity'] ? $this->qty($item->quantity) : null,
                'unit_price' => $display['show_unit_price'] ? $this->money($item->unit_cost) : null,
                'discount' => $display['show_discount'] ? $this->money($item->discount) : null,
                'tax' => $display['show_tax'] ? $this->money($item->tax) : null,
                'line_total' => $display['show_line_total'] ? $this->money($item->line_total) : null,
            ])->all(),
            'totals' => [
                'subtotal' => $display['show_subtotal'] ? $this->money($invoice->subtotal) : null,
                'discount_total' => $display['show_discount_total'] ? $this->money($invoice->discount_total) : null,
                'tax_total' => $display['show_tax_total'] ? $this->money($invoice->tax_total) : null,
                'grand_total' => $display['show_grand_total'] ? $this->money($invoice->grand_total) : null,
                'paid_amount' => $display['show_paid_amount'] ? $this->money($invoice->paid_amount) : null,
                'due_amount' => $display['show_due_amount'] ? $this->money($invoice->due_amount) : null,
                'payment_status' => $display['show_payment_status'] ? $this->paymentStatusLabel($invoice, $locale) : null,
            ],
            'notes' => $display['show_notes'] ? $invoice->notes : null,
            'terms' => $display['show_terms'] ? $this->t($settingsPayload['terms'] ?? [], $locale) : null,
            'closing_note' => $display['show_closing_note'] ? $this->t($settingsPayload['closing_note'] ?? [], $locale) : null,
            'footer_text' => $this->t($settingsPayload['footer_text'] ?? [], $locale),
            'header_text' => $this->t($settingsPayload['header_text'] ?? [], $locale),
            'signature_label' => $display['show_signature'] ? $this->t($settingsPayload['signature_label'] ?? [], $locale) : null,
            'stamp_label' => $display['show_stamp'] ? $this->t($settingsPayload['stamp_label'] ?? [], $locale) : null,
            'authority_name' => $display['show_signature'] ? $this->t($settingsPayload['authority_name'] ?? [], $locale) : null,
            'signature_url' => $display['show_signature'] ? ($settingsPayload['signature_url'] ?? TenantMediaUrl::make($settingsPayload['signature_path'] ?? null)) : null,
            'stamp_url' => $display['show_stamp'] ? ($settingsPayload['stamp_url'] ?? TenantMediaUrl::make($settingsPayload['stamp_path'] ?? null)) : null,
            'column_flags' => [
                'sku' => $display['show_sku'],
                'description' => $display['show_description'],
                'variation' => false,
                'quantity' => $display['show_quantity'],
                'unit_price' => $display['show_unit_price'],
                'discount' => $display['show_discount'],
                'tax' => $display['show_tax'],
                'line_total' => $display['show_line_total'],
            ],
            'labels' => $this->labels($locale),
        ];
    }

    /**
     * يضمن Snapshot للفواتير غير المسودة عند أول طباعة/إصدار.
     */
    public function ensureSnapshot(Model $invoice): void
    {
        if (! $invoice instanceof SalesInvoice && ! $invoice instanceof PurchaseInvoice) {
            return;
        }

        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::tryFrom((string) $invoice->status);

        if (! $status || $status === InvoiceStatus::Draft) {
            return;
        }

        if (! empty($invoice->print_settings_snapshot)) {
            return;
        }

        DB::connection('tenant')->transaction(function () use ($invoice) {
            $locked = $invoice->newQuery()->whereKey($invoice->getKey())->lockForUpdate()->first();
            if (! $locked || ! empty($locked->print_settings_snapshot)) {
                return;
            }

            $locked->print_settings_snapshot = $this->settingsService->buildSnapshot();
            $locked->save();
            $invoice->setAttribute('print_settings_snapshot', $locked->print_settings_snapshot);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveSettingsPayload(Model $invoice, ?string $forceLocale): array
    {
        $status = $invoice->status instanceof InvoiceStatus
            ? $invoice->status
            : InvoiceStatus::tryFrom((string) $invoice->status);

        $useLive = $status === InvoiceStatus::Draft || empty($invoice->print_settings_snapshot);

        if (! $useLive && is_array($invoice->print_settings_snapshot)) {
            $snap = $invoice->print_settings_snapshot;
            $localeInfo = $this->settingsService->resolveLocale(
                null,
                $forceLocale ?: (($snap['default_locale'] ?? 'auto') === 'auto' ? null : ($snap['default_locale'] ?? null))
            );
            if ($forceLocale) {
                $localeInfo = ['locale' => $forceLocale, 'direction' => $forceLocale === 'ar' ? 'rtl' : 'ltr'];
            } elseif (($snap['default_locale'] ?? 'auto') === 'auto') {
                $localeInfo = $this->settingsService->resolveLocale(forceLocale: null);
            } else {
                $localeInfo = $this->settingsService->resolveLocale(forceLocale: $snap['default_locale']);
            }

            $snap['locale'] = $localeInfo['locale'];
            $snap['direction'] = $localeInfo['direction'];
            $snap['display_options'] = array_merge(
                $this->settingsService->defaultDisplayOptions(),
                $snap['display_options'] ?? []
            );

            return $snap;
        }

        $settings = $this->settingsService->getOrCreate();
        $localeInfo = $this->settingsService->resolveLocale($settings, $forceLocale);
        $payload = $this->settingsService->buildSnapshot($settings);
        $payload['locale'] = $localeInfo['locale'];
        $payload['direction'] = $localeInfo['direction'];

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, bool>  $display
     * @return array<string, mixed>
     */
    private function companyBlock(array $payload, string $locale, array $display): array
    {
        return [
            'name' => $this->t($payload['company_name'] ?? [], $locale),
            'legal_name' => $display['show_legal_name'] ? $this->t($payload['legal_name'] ?? [], $locale) : null,
            'logo_url' => $display['show_logo'] ? ($payload['logo_url'] ?? TenantMediaUrl::make($payload['logo_path'] ?? null)) : null,
            'address' => $display['show_address'] ? $this->t($payload['address'] ?? [], $locale) : null,
            'city' => $display['show_address'] ? ($payload['city'] ?? null) : null,
            'country' => $display['show_address'] ? ($payload['country'] ?? null) : null,
            'postal_code' => $display['show_address'] ? ($payload['postal_code'] ?? null) : null,
            'phone' => $display['show_phone'] ? ($payload['phone'] ?? null) : null,
            'email' => $display['show_email'] ? ($payload['email'] ?? null) : null,
            'website' => $display['show_website'] ? ($payload['website'] ?? null) : null,
            'tax_number' => $display['show_tax_number'] ? ($payload['tax_number'] ?? null) : null,
            'commercial_register' => $display['show_commercial_register'] ? ($payload['commercial_register'] ?? null) : null,
            'extra_registration' => $payload['extra_registration'] ?? null,
        ];
    }

    private function watermark(mixed $status): ?string
    {
        $value = $status instanceof InvoiceStatus ? $status->value : (string) $status;

        return match ($value) {
            'draft' => 'draft',
            'cancelled' => 'cancelled',
            'refunded', 'partially_refunded' => 'refunded',
            default => null,
        };
    }

    private function money(mixed $value): string
    {
        return Decimal::money($value ?? '0');
    }

    private function qty(mixed $value): string
    {
        return Decimal::of($value ?? '0');
    }

    /**
     * @param  array<string, string>|mixed  $translations
     */
    private function t(mixed $translations, string $locale, ?string $fallback = null): ?string
    {
        if (! is_array($translations)) {
            return filled($translations) ? (string) $translations : $fallback;
        }

        $value = $translations[$locale] ?? $translations[$locale === 'ar' ? 'en' : 'ar'] ?? null;

        return filled($value) ? (string) $value : $fallback;
    }

    private function paymentStatusLabel(Model $invoice, string $locale): string
    {
        $due = Decimal::money($invoice->due_amount ?? '0');
        $paid = Decimal::money($invoice->paid_amount ?? '0');

        if (Decimal::isZero($due, 2) && Decimal::isPositive($paid, 2)) {
            return __('erp.invoice_statuses.paid', [], $locale);
        }
        if (Decimal::isPositive($paid, 2)) {
            return __('erp.invoice_statuses.partially_paid', [], $locale);
        }

        return __('erp.print.unpaid', [], $locale);
    }

    /**
     * @return array<string, string>
     */
    private function labels(string $locale): array
    {
        return [
            'invoice_number' => __('erp.print.invoice_number', [], $locale),
            'status' => __('erp.fields.status', [], $locale),
            'invoice_date' => __('erp.fields.invoice_date', [], $locale),
            'due_date' => __('erp.fields.due_date', [], $locale),
            'sku' => __('erp.fields.sku', [], $locale),
            'description' => __('erp.fields.description', [], $locale),
            'variation' => __('erp.fields.variation', [], $locale),
            'quantity' => __('erp.fields.quantity', [], $locale),
            'unit_price' => __('erp.fields.unit_price', [], $locale),
            'discount' => __('erp.fields.discount', [], $locale),
            'tax' => __('erp.fields.tax', [], $locale),
            'line_total' => __('erp.fields.line_total', [], $locale),
            'subtotal' => __('erp.fields.subtotal', [], $locale),
            'discount_total' => __('erp.fields.discount_total', [], $locale),
            'tax_total' => __('erp.fields.tax_total', [], $locale),
            'grand_total' => __('erp.fields.grand_total', [], $locale),
            'paid_amount' => __('erp.fields.paid_amount', [], $locale),
            'due_amount' => __('erp.fields.due_amount', [], $locale),
            'payment_status' => __('erp.print.payment_status', [], $locale),
            'notes' => __('erp.fields.notes', [], $locale),
            'terms' => __('erp.print.terms', [], $locale),
            'print' => __('erp.actions.print', [], $locale),
            'back' => __('erp.actions.back', [], $locale),
            'tax_number' => __('erp.print.tax_number', [], $locale),
            'commercial_register' => __('erp.print.commercial_register', [], $locale),
            'sale_number' => __('erp.fields.sale', [], $locale),
            'order_number' => __('erp.fields.order', [], $locale),
            'purchase_order' => __('erp.fields.purchase_order', [], $locale),
            'goods_receipt' => __('erp.fields.goods_receipt', [], $locale),
            'branch' => __('erp.fields.branch', [], $locale),
            'supplier_invoice_number' => __('erp.fields.supplier_invoice_number', [], $locale),
        ];
    }
}
