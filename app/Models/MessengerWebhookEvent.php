<?php

namespace App\Models;

use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class MessengerWebhookEvent extends Model
{
    use CentralConnection;

    protected $table = 'messenger_webhook_events';

    protected $fillable = [
        'provider',
        'event_type',
        'summary',
        'interpretation',
        'page_id',
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
            'processing_status' => MessengerWebhookProcessingStatus::class,
            'interpretation' => 'array',
            'payload' => 'array',
            'original_payload' => 'array',
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
