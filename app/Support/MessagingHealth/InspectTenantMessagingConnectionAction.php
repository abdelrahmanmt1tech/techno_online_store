<?php

namespace App\Support\MessagingHealth;

use App\Messenger\Services\MessengerTenantContextService;
use App\Models\MessengerPageRegistry;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppNumberRegistry;
use App\WhatsApp\Services\WhatsAppTenantContextService;
use RuntimeException;

/**
 * Safe one-tenant connection inspection for Messaging Health Dashboard.
 * Never returns access tokens. Never loads conversations/messages/contacts.
 */
class InspectTenantMessagingConnectionAction
{
    public function __construct(
        protected MessagingHealthSummaryService $summaryService,
        protected WhatsAppTenantContextService $whatsAppTenantContext,
        protected MessengerTenantContextService $messengerTenantContext,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(string $channel, int $registryId): array
    {
        if ($channel === 'whatsapp') {
            return $this->inspectWhatsApp($registryId);
        }

        if ($channel === 'messenger') {
            return $this->inspectMessenger($registryId);
        }

        throw new RuntimeException('Unsupported messaging channel.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function inspectWhatsApp(int $registryId): array
    {
        $registry = WhatsAppNumberRegistry::query()->with('tenant')->findOrFail($registryId);
        $tenant = Tenant::query()->find($registry->tenant_id);

        if ($tenant === null) {
            throw new RuntimeException('Tenant was not found for this registry row.');
        }

        $central = [
            'channel' => 'whatsapp',
            'tenant_id' => (string) $tenant->getTenantKey(),
            'tenant_name' => $tenant->name,
            'tenant_email' => $tenant->email,
            'registry_id' => $registry->id,
            'phone_number_id' => $registry->phone_number_id,
            'display_phone_number' => $registry->display_phone_number,
            'business_name' => $registry->business_name,
            'connection_method' => $registry->connection_method?->value,
            'status' => $registry->status?->value,
            'webhook_status' => $registry->webhook_status,
            'onboarding_status' => $registry->onboarding_status?->value,
            'is_active' => (bool) $registry->is_active,
            'last_inbound_at' => optional($registry->last_inbound_at)?->toDateTimeString(),
            'last_outbound_at' => optional($registry->last_outbound_at)?->toDateTimeString(),
            'last_health_check_at' => optional($registry->last_health_check_at)?->toDateTimeString(),
            'health' => app(WhatsAppRegistryHealthEvaluator::class)->evaluate($registry)->value,
            'recent_webhooks' => $this->summaryService->recentWebhookCountsForAsset(
                'whatsapp',
                (string) $registry->phone_number_id,
            ),
        ];

        $tenantSafe = [
            'token_configured' => false,
            'token_source' => null,
            'last_error_message' => null,
            'reconnect_required_at' => null,
            'inspected' => false,
        ];

        try {
            $this->whatsAppTenantContext->runForTenant($tenant, function () use ($registry, &$tenantSafe) {
                $number = WhatsAppNumber::query()->find($registry->tenant_whatsapp_number_id);

                if ($number === null) {
                    $number = WhatsAppNumber::query()
                        ->where('phone_number_id', $registry->phone_number_id)
                        ->first();
                }

                if ($number === null) {
                    return;
                }

                $tenantSafe = [
                    'token_configured' => filled($number->access_token),
                    'token_source' => $number->token_source?->value ?? $number->token_source,
                    'last_error_message' => $number->last_error_message,
                    'reconnect_required_at' => optional($number->reconnect_required_at)?->toDateTimeString(),
                    'inspected' => true,
                ];
            });
        } finally {
            $this->whatsAppTenantContext->end();
        }

        return array_merge($central, ['tenant_connection' => $tenantSafe]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function inspectMessenger(int $registryId): array
    {
        $registry = MessengerPageRegistry::query()->with('tenant')->findOrFail($registryId);
        $tenant = Tenant::query()->find($registry->tenant_id);

        if ($tenant === null) {
            throw new RuntimeException('Tenant was not found for this registry row.');
        }

        $central = [
            'channel' => 'messenger',
            'tenant_id' => (string) $tenant->getTenantKey(),
            'tenant_name' => $tenant->name,
            'tenant_email' => $tenant->email,
            'registry_id' => $registry->id,
            'page_id' => $registry->page_id,
            'page_name' => $registry->page_name,
            'connection_method' => $registry->connection_method?->value,
            'token_source' => $registry->token_source?->value,
            'status' => $registry->status?->value,
            'webhook_status' => $registry->webhook_status,
            'onboarding_status' => null,
            'is_active' => (bool) $registry->is_active,
            'last_inbound_at' => optional($registry->last_inbound_at)?->toDateTimeString(),
            'last_outbound_at' => optional($registry->last_outbound_at)?->toDateTimeString(),
            'last_health_check_at' => optional($registry->last_health_check_at)?->toDateTimeString(),
            'health' => app(MessengerRegistryHealthEvaluator::class)->evaluate($registry)->value,
            'recent_webhooks' => $this->summaryService->recentWebhookCountsForAsset(
                'messenger',
                (string) $registry->page_id,
            ),
        ];

        $tenantSafe = [
            'token_configured' => false,
            'token_source' => null,
            'last_error_message' => null,
            'reconnect_required_at' => null,
            'inspected' => false,
        ];

        try {
            $this->messengerTenantContext->runForTenant($tenant, function () use ($registry, &$tenantSafe) {
                $page = MessengerPage::query()->find($registry->tenant_messenger_page_id);

                if ($page === null) {
                    $page = MessengerPage::query()
                        ->where('page_id', $registry->page_id)
                        ->first();
                }

                if ($page === null) {
                    return;
                }

                $tenantSafe = [
                    'token_configured' => filled($page->page_access_token),
                    'token_source' => $page->token_source?->value ?? $page->token_source,
                    'last_error_message' => $page->last_error_message,
                    'reconnect_required_at' => optional($page->reconnect_required_at)?->toDateTimeString(),
                    'inspected' => true,
                ];
            });
        } finally {
            $this->messengerTenantContext->end();
        }

        return array_merge($central, ['tenant_connection' => $tenantSafe]);
    }
}
