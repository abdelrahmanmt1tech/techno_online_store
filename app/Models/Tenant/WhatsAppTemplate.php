<?php

namespace App\Models\Tenant;

use App\WhatsApp\Enums\WhatsAppTemplateCategory;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppTemplate extends Model
{
    protected $connection = 'tenant';

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'whatsapp_number_id',
        'whatsapp_business_account_id',
        'provider_template_id',
        'name',
        'language',
        'category',
        'status',
        'components',
        'variables_schema',
        'raw_payload',
        'is_disabled_locally',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => WhatsAppTemplateCategory::class,
            'status' => WhatsAppTemplateStatus::class,
            'components' => 'array',
            'variables_schema' => 'array',
            'raw_payload' => 'array',
            'is_disabled_locally' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function whatsappNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppNumber::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'template_id');
    }

    public function isSendable(): bool
    {
        return $this->status === WhatsAppTemplateStatus::Approved && ! $this->is_disabled_locally;
    }
}
