<?php

namespace App\Services\Erp;

use App\Models\Setting;
use App\Models\Tenant\InvoicePrintSetting;
use App\Support\Erp\TenantMediaUrl;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * إعدادات طباعة الفواتير — سجل واحد لكل Tenant مع Defaults آمنة.
 */
final class InvoicePrintSettingsService
{
    /**
     * @return array<string, bool>
     */
    public function defaultDisplayOptions(): array
    {
        return [
            'show_logo' => true,
            'show_legal_name' => true,
            'show_address' => true,
            'show_phone' => true,
            'show_email' => true,
            'show_website' => false,
            'show_tax_number' => true,
            'show_commercial_register' => true,
            'show_status' => true,
            'show_invoice_date' => true,
            'show_due_date' => true,
            'show_sale_number' => true,
            'show_order_id' => true,
            'show_purchase_order' => true,
            'show_goods_receipt' => true,
            'show_branch' => true,
            'show_created_by' => false,
            'show_customer_name' => true,
            'show_customer_phone' => true,
            'show_customer_email' => true,
            'show_customer_address' => true,
            'show_supplier_name' => true,
            'show_supplier_phone' => true,
            'show_supplier_email' => true,
            'show_supplier_address' => true,
            'show_party_tax_number' => false,
            'show_sku' => true,
            'show_description' => true,
            'show_variation' => true,
            'show_unit' => false,
            'show_quantity' => true,
            'show_unit_price' => true,
            'show_discount' => true,
            'show_tax' => true,
            'show_line_total' => true,
            'show_subtotal' => true,
            'show_discount_total' => true,
            'show_tax_total' => true,
            'show_grand_total' => true,
            'show_paid_amount' => true,
            'show_due_amount' => true,
            'show_payment_status' => true,
            'show_notes' => true,
            'show_terms' => true,
            'show_closing_note' => true,
            'show_signature' => true,
            'show_stamp' => true,
        ];
    }

    public function getOrCreate(): InvoicePrintSetting
    {
        return DB::connection('tenant')->transaction(function () {
            $existing = InvoicePrintSetting::query()->lockForUpdate()->first();
            if ($existing) {
                return $existing;
            }

            return InvoicePrintSetting::query()->create($this->defaultAttributes());
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultAttributes(): array
    {
        $siteName = $this->settingValue('site_name');
        $logo = $this->settingValue('site_logo') ?: $this->settingValue('dashboard_logo');

        return [
            'company_name' => ['en' => $siteName ?: 'Company', 'ar' => $siteName ?: 'المنشأة'],
            'legal_name' => ['en' => $siteName ?: '', 'ar' => $siteName ?: ''],
            'logo_path' => $logo,
            'primary_color' => '#065f46',
            'logo_width' => 140,
            'paper_size' => 'A4',
            'paper_orientation' => 'portrait',
            'default_locale' => 'auto',
            'logo_align' => 'start',
            'sales_invoice_title' => ['en' => 'Tax Invoice', 'ar' => 'فاتورة مبيعات'],
            'purchase_invoice_title' => ['en' => 'Purchase Invoice', 'ar' => 'فاتورة مشتريات'],
            'header_text' => ['en' => '', 'ar' => ''],
            'closing_note' => ['en' => 'Thank you for your business.', 'ar' => 'شكرًا لتعاملكم معنا.'],
            'terms' => ['en' => '', 'ar' => ''],
            'footer_text' => ['en' => '', 'ar' => ''],
            'authority_name' => ['en' => 'Authorized signature', 'ar' => 'التوقيع المعتمد'],
            'signature_label' => ['en' => 'Signature', 'ar' => 'التوقيع'],
            'stamp_label' => ['en' => 'Stamp', 'ar' => 'الختم'],
            'display_options' => $this->defaultDisplayOptions(),
            'layout_options' => [],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function mergedDisplayOptions(?InvoicePrintSetting $settings = null): array
    {
        $settings ??= $this->getOrCreate();

        return array_merge($this->defaultDisplayOptions(), $settings->display_options ?? []);
    }

    /**
     * تحديد لغة واتجاه الطباعة.
     *
     * @return array{locale: string, direction: string}
     */
    public function resolveLocale(?InvoicePrintSetting $settings = null, ?string $forceLocale = null): array
    {
        $settings ??= $this->getOrCreate();
        $choice = $forceLocale ?: ($settings->default_locale ?: 'auto');

        $locale = match ($choice) {
            'ar', 'en' => $choice,
            default => app()->getLocale() === 'ar' ? 'ar' : 'en',
        };

        return [
            'locale' => $locale,
            'direction' => $locale === 'ar' ? 'rtl' : 'ltr',
        ];
    }

    /**
     * لقطة كاملة للطباعة التاريخية (تشمل روابط الوسائط المحلولة وقت الالتقاط).
     *
     * @return array<string, mixed>
     */
    public function buildSnapshot(?InvoicePrintSetting $settings = null): array
    {
        $settings ??= $this->getOrCreate();
        $localeMeta = $this->resolveLocale($settings);

        return [
            'captured_at' => now()->toIso8601String(),
            'company_name' => $settings->getTranslations('company_name'),
            'legal_name' => $settings->getTranslations('legal_name'),
            'logo_path' => $settings->logo_path,
            'logo_url' => TenantMediaUrl::make($settings->logo_path),
            'tax_number' => $settings->tax_number,
            'commercial_register' => $settings->commercial_register,
            'phone' => $settings->phone,
            'email' => $settings->email,
            'website' => $settings->website,
            'address' => $settings->getTranslations('address'),
            'city' => $settings->city,
            'country' => $settings->country,
            'postal_code' => $settings->postal_code,
            'extra_registration' => $settings->extra_registration,
            'primary_color' => $settings->primary_color ?: '#065f46',
            'logo_width' => (int) ($settings->logo_width ?: 140),
            'paper_size' => $settings->paper_size ?: 'A4',
            'paper_orientation' => $settings->paper_orientation ?: 'portrait',
            'default_locale' => $settings->default_locale ?: 'auto',
            'logo_align' => $settings->logo_align ?: 'start',
            'sales_invoice_title' => $settings->getTranslations('sales_invoice_title'),
            'purchase_invoice_title' => $settings->getTranslations('purchase_invoice_title'),
            'header_text' => $settings->getTranslations('header_text'),
            'closing_note' => $settings->getTranslations('closing_note'),
            'terms' => $settings->getTranslations('terms'),
            'footer_text' => $settings->getTranslations('footer_text'),
            'authority_name' => $settings->getTranslations('authority_name'),
            'signature_label' => $settings->getTranslations('signature_label'),
            'stamp_label' => $settings->getTranslations('stamp_label'),
            'stamp_path' => $settings->stamp_path,
            'stamp_url' => TenantMediaUrl::make($settings->stamp_path),
            'signature_path' => $settings->signature_path,
            'signature_url' => TenantMediaUrl::make($settings->signature_path),
            'display_options' => $this->mergedDisplayOptions($settings),
            'layout_options' => $settings->layout_options ?? [],
            'resolved_locale_default' => $localeMeta,
        ];
    }

    public function touchUpdater(InvoicePrintSetting $settings): void
    {
        $settings->updated_by = Auth::guard('tenant')->id();
        $settings->save();
    }

    private function settingValue(string $key): ?string
    {
        try {
            $row = Setting::query()->where('key', $key)->first();

            return $row?->value ? (string) $row->value : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
