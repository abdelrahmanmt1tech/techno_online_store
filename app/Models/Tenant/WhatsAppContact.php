<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppContact extends Model
{
    protected $connection = 'tenant';

    protected $table = 'whatsapp_contacts';

    protected $fillable = [
        'phone',
        'profile_name',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'customer_phone', 'phone');
    }
}
