<?php

namespace App\Models;

use App\WhatsApp\Enums\WhatsAppWebhookProcessingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class WhatsAppWebhookEvent extends Model
{
    use CentralConnection;

    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
        'provider',
        'event_type',
        'phone_number_id',
        'tenant_id',
        'processing_status',
        'payload',
        'payload_redacted',
        'signature_valid',
        'diagnostic_data',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processing_status' => WhatsAppWebhookProcessingStatus::class,
            'payload' => 'array',
            'payload_redacted' => 'boolean',
            'signature_valid' => 'boolean',
            'diagnostic_data' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
