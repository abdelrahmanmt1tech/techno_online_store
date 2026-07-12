<?php

namespace App\Models\Tenant;

use App\Messenger\Enums\MessengerConnectionMethod;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Enums\MessengerTokenSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MessengerPage extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'messenger_pages';

    protected $fillable = [
        'page_id',
        'page_name',
        'page_access_token',
        'token_source',
        'connection_method',
        'status',
        'webhook_status',
        'is_default',
        'is_active',
        'last_error_message',
        'last_inbound_at',
        'last_outbound_at',
        'last_health_check_at',
        'connected_at',
        'disconnected_at',
        'reconnect_required_at',
    ];

    protected $hidden = [
        'page_access_token',
    ];

    protected $attributes = [
        'token_source' => MessengerTokenSource::Manual->value,
        'connection_method' => MessengerConnectionMethod::Manual->value,
        'status' => MessengerPageStatus::Active->value,
        'is_default' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'page_access_token' => 'encrypted',
            'token_source' => MessengerTokenSource::class,
            'connection_method' => MessengerConnectionMethod::class,
            'status' => MessengerPageStatus::class,
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

    protected static function booted(): void
    {
        static::creating(function (MessengerPage $page): void {
            if ($page->connected_at === null
                && $page->status === MessengerPageStatus::Active) {
                $page->connected_at = now();
            }
        });
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(MessengerConversation::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(MessengerMessage::class);
    }

    public function apiRequests(): HasMany
    {
        return $this->hasMany(MessengerApiRequest::class);
    }

    public function getMaskedPageAccessTokenAttribute(): string
    {
        if (blank($this->page_access_token)) {
            return '';
        }

        return '********';
    }
}
