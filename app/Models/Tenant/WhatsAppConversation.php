<?php

namespace App\Models\Tenant;

use App\Models\TenantUser;
use App\WhatsApp\Enums\WhatsAppConversationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversation extends Model
{
    protected $connection = 'tenant';

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'whatsapp_number_id',
        'customer_phone',
        'customer_name',
        'assigned_user_id',
        'status',
        'last_message_preview',
        'last_message_at',
        'last_customer_message_at',
        'last_outbound_message_at',
        'customer_service_window_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppConversationStatus::class,
            'last_message_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
            'last_outbound_message_at' => 'datetime',
            'customer_service_window_expires_at' => 'datetime',
        ];
    }

    public function whatsappNumber(): BelongsTo
    {
        return $this->belongsTo(WhatsAppNumber::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id');
    }

    public function canSendFreeformReply(): bool
    {
        if ($this->customer_service_window_expires_at === null) {
            return false;
        }

        return now()->lt($this->customer_service_window_expires_at);
    }
}
