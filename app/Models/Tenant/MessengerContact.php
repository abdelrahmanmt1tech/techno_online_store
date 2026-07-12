<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessengerContact extends Model
{
    protected $connection = 'tenant';

    protected $table = 'messenger_contacts';

    protected $fillable = [
        'psid',
        'profile_name',
        'profile_picture_url',
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
        return $this->hasMany(MessengerConversation::class, 'contact_id');
    }
}
