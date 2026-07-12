<?php

namespace App\Models\Tenant;

use App\Messenger\Enums\MessengerMessageDirection;
use App\Messenger\Enums\MessengerMessageSenderType;
use App\Messenger\Enums\MessengerMessageStatus;
use App\Messenger\Enums\MessengerMessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessengerMessage extends Model
{
    protected $connection = 'tenant';

    protected $table = 'messenger_messages';

    protected $fillable = [
        'conversation_id',
        'messenger_page_id',
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
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'direction' => MessengerMessageDirection::class,
            'sender_type' => MessengerMessageSenderType::class,
            'type' => MessengerMessageType::class,
            'status' => MessengerMessageStatus::class,
            'media_metadata' => 'array',
            'raw_payload' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(MessengerConversation::class, 'conversation_id');
    }

    public function messengerPage(): BelongsTo
    {
        return $this->belongsTo(MessengerPage::class);
    }
}
