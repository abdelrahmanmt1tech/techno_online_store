<?php

namespace App\Models\Tenant;

use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhatsAppNumber extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'whatsapp_numbers';

    protected $fillable = [
        'display_phone_number',
        'phone_number_id',
        'whatsapp_business_account_id',
        'business_name',
        'access_token',
        'token_type',
        'status',
        'webhook_status',
        'is_default',
        'is_active',
        'last_error_message',
        'last_inbound_at',
        'last_outbound_at',
        'last_health_check_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'status' => WhatsAppConnectionStatus::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(WhatsAppTemplate::class);
    }

    public function getMaskedAccessTokenAttribute(): string
    {
        if (blank($this->access_token)) {
            return '';
        }

        return '********';
    }
}
