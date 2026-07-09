<?php

namespace App\Models;

use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class WhatsAppNumberRegistry extends Model
{
    use CentralConnection;

    protected $table = 'whatsapp_number_registry';

    protected $fillable = [
        'tenant_id',
        'tenant_whatsapp_number_id',
        'display_phone_number',
        'phone_number_id',
        'whatsapp_business_account_id',
        'business_name',
        'status',
        'webhook_status',
        'is_default',
        'is_active',
        'quality_rating',
        'last_inbound_at',
        'last_outbound_at',
        'last_health_check_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => WhatsAppConnectionStatus::class,
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
