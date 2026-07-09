<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppNumberRegistry;

class SyncWhatsAppNumberRegistryAction
{
    public function execute(WhatsAppNumber $number): WhatsAppNumberRegistry
    {
        $tenant = tenant();

        if ($tenant === null) {
            throw new \RuntimeException('Tenant context is required to sync WhatsApp number registry.');
        }

        return WhatsAppNumberRegistry::query()->updateOrCreate(
            ['phone_number_id' => $number->phone_number_id],
            [
                'tenant_id' => $tenant->getTenantKey(),
                'tenant_whatsapp_number_id' => $number->getKey(),
                'display_phone_number' => $number->display_phone_number,
                'whatsapp_business_account_id' => $number->whatsapp_business_account_id,
                'business_name' => $number->business_name,
                'status' => $number->status,
                'webhook_status' => $number->webhook_status,
                'is_default' => (bool) ($number->is_default ?? false),
                'is_active' => (bool) ($number->is_active ?? true),
                'last_inbound_at' => $number->last_inbound_at,
                'last_outbound_at' => $number->last_outbound_at,
                'last_health_check_at' => $number->last_health_check_at,
            ],
        );
    }

    public function deleteFromRegistry(WhatsAppNumber $number): void
    {
        WhatsAppNumberRegistry::query()
            ->where('phone_number_id', $number->phone_number_id)
            ->delete();
    }
}
