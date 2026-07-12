<?php

namespace App\Models\Tenant;

use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppTokenSource;
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
        'connection_method',
        'onboarding_status',
        'coexistence_enabled',
        'business_app_number',
        'token_source',
        'status',
        'webhook_status',
        'is_default',
        'is_active',
        'last_error_message',
        'last_onboarding_error',
        'last_inbound_at',
        'last_outbound_at',
        'last_health_check_at',
        'connected_at',
        'disconnected_at',
        'reconnect_required_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $attributes = [
        'token_type' => 'manual',
        'connection_method' => WhatsAppConnectionMethod::ManualApiOnly->value,
        'onboarding_status' => WhatsAppOnboardingStatus::Completed->value,
        'coexistence_enabled' => false,
        'token_source' => WhatsAppTokenSource::Manual->value,
        'status' => WhatsAppConnectionStatus::Active->value,
        'is_default' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'connection_method' => WhatsAppConnectionMethod::class,
            'onboarding_status' => WhatsAppOnboardingStatus::class,
            'token_source' => WhatsAppTokenSource::class,
            'status' => WhatsAppConnectionStatus::class,
            'coexistence_enabled' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'last_health_check_at' => 'datetime',
            'connected_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'reconnect_required_at' => 'datetime',
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

    protected static function booted(): void
    {
        static::creating(function (WhatsAppNumber $number): void {
            if ($number->connection_method === null) {
                $number->connection_method = WhatsAppConnectionMethod::ManualApiOnly;
            }

            if ($number->token_source === null) {
                $number->token_source = WhatsAppTokenSource::Manual;
            }

            if ($number->onboarding_status === null) {
                $number->onboarding_status = WhatsAppOnboardingStatus::Completed;
            }

            if ($number->connected_at === null
                && $number->onboarding_status === WhatsAppOnboardingStatus::Completed) {
                $number->connected_at = now();
            }
        });
    }

    public function getMaskedAccessTokenAttribute(): string
    {
        if (blank($this->access_token)) {
            return '';
        }

        return '********';
    }
}
