<?php

namespace App\Support\MetaReset;

use InvalidArgumentException;

/**
 * Authoritative registry of Meta integration tables that participate in reset.
 *
 * Any new WhatsApp/Messenger (Meta) integration table must be registered here
 * (or explicitly documented as excluded) before the migration is considered complete.
 *
 * Deletion priority: lower number deleted first (children before parents).
 */
final class MetaIntegrationResetRegistry
{
    public const SCOPE_ALL = 'all';

    public const SCOPE_WHATSAPP = 'whatsapp';

    public const SCOPE_MESSENGER = 'messenger';

    public const DB_CENTRAL = 'central';

    public const DB_TENANT = 'tenant';

    /**
     * @return list<MetaIntegrationResetTable>
     */
    public function all(): array
    {
        return [
            // ---- WhatsApp central (children → registry) ----
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_CENTRAL,
                table: 'whatsapp_webhook_events',
                priority: 10,
                optional: false,
                description: 'Central inbound webhook diagnostics',
                reason: 'Channel-specific routing/diagnostics; no business CRM',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_CENTRAL,
                table: 'whatsapp_onboarding_sessions',
                priority: 20,
                optional: false,
                description: 'Embedded Signup / coexistence onboarding sessions',
                reason: 'Transient onboarding state including encrypted tokens',
                mayContainCredentials: true,
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_CENTRAL,
                table: 'whatsapp_number_registry',
                priority: 30,
                optional: false,
                description: 'Central WhatsApp number routing registry',
                reason: 'Maps phone_number_id → tenant; no tokens stored centrally',
            ),

            // ---- WhatsApp tenant (FK order from migrations) ----
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_TENANT,
                table: 'whatsapp_api_requests',
                priority: 10,
                optional: false,
                description: 'Outbound Graph API request logs',
                reason: 'WhatsApp-only diagnostics; FK to numbers/messages',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_TENANT,
                table: 'whatsapp_messages',
                priority: 20,
                optional: false,
                description: 'WhatsApp conversation messages',
                reason: 'Channel messages; FK to conversations/numbers/templates',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_TENANT,
                table: 'whatsapp_conversations',
                priority: 30,
                optional: false,
                description: 'WhatsApp inbox conversations',
                reason: 'Channel conversations; FK to numbers',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_TENANT,
                table: 'whatsapp_contacts',
                priority: 40,
                optional: false,
                description: 'WhatsApp phone contacts',
                reason: 'Dedicated WhatsApp contact table (not shared CRM customers)',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_TENANT,
                table: 'whatsapp_templates',
                priority: 50,
                optional: false,
                description: 'Synced WhatsApp message templates',
                reason: 'WABA template cache; FK to numbers (nullable)',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_WHATSAPP,
                scope: self::DB_TENANT,
                table: 'whatsapp_numbers',
                priority: 60,
                optional: false,
                description: 'Connected WhatsApp Business phone numbers',
                reason: 'Tenant operational assets including encrypted access tokens',
                mayContainCredentials: true,
            ),

            // ---- Messenger central ----
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_CENTRAL,
                table: 'messenger_webhook_events',
                priority: 10,
                optional: false,
                description: 'Central Messenger webhook diagnostics',
                reason: 'Channel-specific routing/diagnostics',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_CENTRAL,
                table: 'messenger_onboarding_sessions',
                priority: 20,
                optional: false,
                description: 'Facebook Login / Page picker onboarding sessions',
                reason: 'Transient onboarding state including encrypted tokens',
                mayContainCredentials: true,
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_CENTRAL,
                table: 'messenger_page_registry',
                priority: 30,
                optional: false,
                description: 'Central Messenger Page routing registry',
                reason: 'Maps page_id → tenant; no page tokens stored centrally',
            ),

            // ---- Messenger tenant ----
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_TENANT,
                table: 'messenger_api_requests',
                priority: 10,
                optional: false,
                description: 'Outbound Messenger Graph API request logs',
                reason: 'Messenger-only diagnostics; FK to pages/messages',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_TENANT,
                table: 'messenger_messages',
                priority: 20,
                optional: false,
                description: 'Messenger conversation messages',
                reason: 'Channel messages; FK to conversations/pages',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_TENANT,
                table: 'messenger_conversations',
                priority: 30,
                optional: false,
                description: 'Messenger inbox conversations',
                reason: 'Channel conversations; FK to pages/contacts',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_TENANT,
                table: 'messenger_contacts',
                priority: 40,
                optional: false,
                description: 'Messenger PSID contacts',
                reason: 'Dedicated Messenger contact table (not shared CRM customers)',
            ),
            new MetaIntegrationResetTable(
                channel: self::SCOPE_MESSENGER,
                scope: self::DB_TENANT,
                table: 'messenger_pages',
                priority: 50,
                optional: false,
                description: 'Connected Facebook Pages',
                reason: 'Tenant operational assets including encrypted page access tokens',
                mayContainCredentials: true,
            ),
        ];
    }

    /**
     * @return list<string>
     */
    public function allowedScopes(): array
    {
        return [self::SCOPE_ALL, self::SCOPE_WHATSAPP, self::SCOPE_MESSENGER];
    }

    public function assertValidScope(string $scope): void
    {
        if (! in_array($scope, $this->allowedScopes(), true)) {
            throw new InvalidArgumentException('Invalid Meta reset scope.');
        }
    }

    /**
     * @return list<MetaIntegrationResetTable>
     */
    public function forScope(string $scope): array
    {
        $this->assertValidScope($scope);

        $tables = $this->all();

        if ($scope === self::SCOPE_ALL) {
            return $tables;
        }

        return array_values(array_filter(
            $tables,
            fn (MetaIntegrationResetTable $table) => $table->channel === $scope
        ));
    }

    /**
     * @return list<MetaIntegrationResetTable>
     */
    public function forScopeAndDatabase(string $scope, string $databaseScope): array
    {
        $tables = $this->forScope($scope);

        $filtered = array_values(array_filter(
            $tables,
            fn (MetaIntegrationResetTable $table) => $table->scope === $databaseScope
        ));

        usort($filtered, fn (MetaIntegrationResetTable $a, MetaIntegrationResetTable $b) => $a->priority <=> $b->priority);

        return $filtered;
    }

    /**
     * Tables and data classes that must never be deleted by this tool.
     *
     * @return list<string>
     */
    public function preservedExamples(): array
    {
        return [
            'tenants',
            'domains',
            'admins',
            'users (tenant)',
            'roles / permissions',
            'products / orders / categories',
            'settings',
            'meta_integration_reset_runs (audit)',
            'migrations',
        ];
    }
}
