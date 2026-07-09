<?php

namespace App\Models\Tenant;

use App\WhatsApp\Enums\WhatsAppMessageDirection;
use App\WhatsApp\Enums\WhatsAppMessageSenderType;
use App\WhatsApp\Enums\WhatsAppMessageStatus;
use App\WhatsApp\Enums\WhatsAppMessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    protected $connection = 'tenant';

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'conversation_id',
        'whatsapp_number_id',
        'provider_message_id',
        'direction',
        'sender_type',
        'type',
        'body',
        'media_metadata',
        'raw_payload',
        'status',
        'error_code',
        'error_message',
        'template_id',
        'template_name',
        'template_language',
        'template_variables',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => WhatsAppMessageDirection::class,
            'sender_type' => WhatsAppMessageSenderType::class,
            'type' => WhatsAppMessageType::class,
            'status' => WhatsAppMessageStatus::class,
            'media_metadata' => 'array',
            'raw_payload' => 'array',
            'template_variables' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class);
    }

    public function whatsappNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppNumber::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class);
    }
}
