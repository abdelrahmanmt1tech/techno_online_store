<?php

namespace App\Models;

use App\Messenger\Enums\MessengerConnectionMethod;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Enums\MessengerTokenSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class MessengerPageRegistry extends Model
{
    use CentralConnection;

    protected $table = 'messenger_page_registry';

    protected $fillable = [
        'tenant_id',
        'tenant_messenger_page_id',
        'page_id',
        'page_name',
        'connection_method',
        'token_source',
        'status',
        'webhook_status',
        'is_default',
        'is_active',
        'last_inbound_at',
        'last_outbound_at',
        'last_health_check_at',
    ];

    protected function casts(): array
    {
        return [
            'connection_method' => MessengerConnectionMethod::class,
            'token_source' => MessengerTokenSource::class,
            'status' => MessengerPageStatus::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'last_inbound_at' => 'datetime',
            'last_outbound_at' => 'datetime',
            'last_health_check_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
