# Messaging Health Dashboard

**Phase:** H  
**Audience:** Platform operations / support (Admin panel)  
**Route:** `/admin/messaging-health`  
**Class:** `App\Filament\Pages\MessagingHealthDashboard`

## Purpose

Central health and diagnostics overview for **WhatsApp** and **Messenger** integrations across tenants.

It is **not**:
- a cross-tenant operational inbox
- a place to read conversations, messages, or contacts
- a token vault or OAuth manager

## Architecture

| Data | Source |
|---|---|
| Global summary cards | Central `whatsapp_number_registry`, `messenger_page_registry` |
| Attention table | Same registries (+ tenant name relation) |
| Webhook aggregates | Central `whatsapp_webhook_events`, `messenger_webhook_events` |
| Optional “Inspect tenant connection” | Initializes **one** tenant, loads that asset only, ends context |

**Rule:** Global dashboard load never loops tenants and never calls Meta Graph API.

## Health states

Enum: `App\Support\MessagingHealth\MessagingHealthStatus`

| State | Typical meaning |
|---|---|
| `healthy` | Active + subscribed |
| `warning` | Active but webhook not subscribed |
| `reconnect_required` | Registry status reconnect_required |
| `failed` | Registry (or WA onboarding) failed |
| `disabled` | Inactive / disabled |
| `pending` | WhatsApp onboarding in progress |
| `unknown` | Inconsistent / unmatched metadata |

Evaluators:
- `WhatsAppRegistryHealthEvaluator`
- `MessengerRegistryHealthEvaluator`

Documented rule order is in class docblocks (first match wins).

## Attention table

Shows registry integrations (not conversations). Filters: channel, tenant, health, status, webhook status, search, needs-attention-only.

Asset IDs are **masked** in the table. Tokens are never shown.

## Tenant inspection safety

Action: `InspectTenantMessagingConnectionAction`

1. Validate registry + central tenant exist  
2. Initialize that tenant only  
3. Load WhatsAppNumber / MessengerPage for the registry asset  
4. Return safe fields only (`token_configured` yes/no, `token_source`, safe error, reconnect timestamp)  
5. End tenant context in `finally`

Does **not** load conversations, messages, or contacts.

## Allowed actions

- Refresh dashboard (local DB queries)
- Open existing registry resources
- Open webhook event resources
- Inspect tenant connection (safe)

## Intentionally excluded

- Automatic Graph health checks / polling
- Test message send
- Automatic reconnect / token refresh
- Cross-tenant conversation search
- Instagram / Orders / campaigns / unified inbox
- Ticketing system

Future optional: read-only Graph probe that updates `last_health_check_at` without exposing tokens (not implemented in Phase H).

## Permissions

Reuses existing platform troubleshoot gates:

- `whatsapp.platform.troubleshoot`
- `messenger.platform.troubleshoot`

No new permission keys in Phase H (development bypass still applies).

## Related docs

- [`docs/whatsapp-messaging-module.md`](whatsapp-messaging-module.md)
- [`docs/messenger-messaging-module.md`](messenger-messaging-module.md)
