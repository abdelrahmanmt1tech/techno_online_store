<?php

namespace App\Models\Tenant;

use App\Messenger\Enums\MessengerConversationStatus;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessengerConversation extends Model
{
    protected $connection = 'tenant';

    protected $table = 'messenger_conversations';

    protected $fillable = [
        'messenger_page_id',
        'sender_psid',
        'customer_name',
        'contact_id',
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
            'status' => MessengerConversationStatus::class,
            'last_message_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
            'last_outbound_message_at' => 'datetime',
            'customer_service_window_expires_at' => 'datetime',
        ];
    }

    public function messengerPage(): BelongsTo
    {
        return $this->belongsTo(MessengerPage::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(MessengerContact::class, 'contact_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'assigned_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessengerMessage::class, 'conversation_id');
    }

    public function canSendFreeformReply(): bool
    {
        if ($this->customer_service_window_expires_at === null) {
            return false;
        }

        return now()->lt($this->customer_service_window_expires_at);
    }
}
