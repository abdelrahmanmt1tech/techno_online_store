<?php

namespace App\Models\Tenant;

use App\WhatsApp\Enums\WhatsAppApiRequestOperation;
use App\WhatsApp\Enums\WhatsAppApiRequestOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppApiRequest extends Model
{
    protected $table = 'whatsapp_api_requests';

    protected $fillable = [
        'whatsapp_number_id',
        'whatsapp_message_id',
        'operation',
        'recipient_phone',
        'http_status',
        'api_error_code',
        'outcome',
        'status_label',
        'summary',
        'request_payload',
        'response_body',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'operation' => WhatsAppApiRequestOperation::class,
            'outcome' => WhatsAppApiRequestOutcome::class,
            'request_payload' => 'array',
            'response_body' => 'array',
        ];
    }

    public function whatsappNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppNumber::class);
    }

    public function whatsappMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class);
    }
}
