<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppOnboardingSession extends Model
{
    protected $table = 'whatsapp_onboarding_sessions';

    protected $fillable = [
        'nonce',
        'tenant_id',
        'user_id',
        'connection_method',
        'status',
        'waba_id',
        'phone_number_id',
        'display_phone_number',
        'business_id',
        'meta_event',
        'session_payload',
        'access_token',
        'tenant_whatsapp_number_id',
        'last_error',
        'return_url',
        'expires_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'session_payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function getMaskedAccessTokenAttribute(): string
    {
        return filled($this->access_token) ? '********' : '';
    }
}
