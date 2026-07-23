<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class InvoicePrintSetting extends Model
{
    use BelongsToTenantConnection;
    use HasTranslations;

    /** @var list<string> */
    public array $translatable = [
        'company_name',
        'legal_name',
        'address',
        'sales_invoice_title',
        'purchase_invoice_title',
        'header_text',
        'closing_note',
        'terms',
        'footer_text',
        'authority_name',
        'signature_label',
        'stamp_label',
    ];

    protected $fillable = [
        'company_name',
        'legal_name',
        'logo_path',
        'tax_number',
        'commercial_register',
        'phone',
        'email',
        'website',
        'address',
        'city',
        'country',
        'postal_code',
        'extra_registration',
        'primary_color',
        'logo_width',
        'paper_size',
        'paper_orientation',
        'default_locale',
        'logo_align',
        'sales_invoice_title',
        'purchase_invoice_title',
        'header_text',
        'closing_note',
        'terms',
        'footer_text',
        'authority_name',
        'signature_label',
        'stamp_label',
        'stamp_path',
        'signature_path',
        'display_options',
        'layout_options',
        'updated_by',
    ];

    protected $casts = [
        'display_options' => 'array',
        'layout_options' => 'array',
        'logo_width' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }

    public function translated(string $attribute, ?string $locale = null, ?string $fallback = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $this->getTranslation($attribute, $locale, false);

        if (filled($value)) {
            return $value;
        }

        $fallback ??= $locale === 'ar' ? 'en' : 'ar';

        return $this->getTranslation($attribute, $fallback, false) ?: null;
    }
}
