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
        'summary',
        'interpretation',
        'phone_number_id',
        'tenant_id',
        'processing_status',
        'payload',
        'original_payload',
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
            'interpretation' => 'array',
            'payload' => 'array',
            'original_payload' => 'array',
            'payload_redacted' => 'boolean',
            'signature_valid' => 'boolean',
            'diagnostic_data' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function reprocessablePayload(): ?array
    {
        $payload = $this->original_payload ?? $this->payload;

        return is_array($payload) ? $payload : null;
    }

    public function canReprocess(): bool
    {
        if ($this->event_type === 'invalid_signature') {
            return false;
        }

        if (! in_array($this->processing_status, [
            WhatsAppWebhookProcessingStatus::Failed,
            WhatsAppWebhookProcessingStatus::Unresolved,
        ], true)) {
            return false;
        }

        $payload = $this->reprocessablePayload();

        return is_array($payload) && ($payload['entry'] ?? []) !== [];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
