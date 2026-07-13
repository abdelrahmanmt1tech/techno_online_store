# WhatsApp Messaging Module Documentation

Developer handoff document for the WhatsApp Business Cloud API messaging module in the Techno Online Store multi-tenant platform.

---

## 1. Purpose

This module was built for a **multi-tenant ecommerce + CRM platform** where each merchant (tenant) can:

- Connect one or more **WhatsApp Business Cloud API** numbers
- Receive inbound customer messages via Meta webhooks
- Reply from a **selected WhatsApp number**
- Send **approved templates** when required by WhatsApp policy
- Enforce WhatsApp’s **24-hour customer service window**

The implementation uses the **official WhatsApp Cloud API** only. There is no unofficial QR / WhatsApp Web integration.

**Current scope:** Manual Cloud API connection is **complete and stable** (staging E2E passed). WhatsApp **API Only Embedded Signup (Phases A–D) is code-complete** — automated tests pass — but **real-number staging E2E is still pending** (needs a spare Cloud API–suitable WhatsApp number). **Coexistence remains a later phase.** Orders notifications remain postponed. Messenger is a separate channel.

---

## 2. Business context

| Topic | Decision |
|---|---|
| **Platform owner** | Techno Web Masr |
| **Merchant ownership** | Each merchant/tenant owns their own WhatsApp Business assets |
| **Billing** | Customer-direct — each merchant pays Meta directly for WhatsApp usage |
| **Platform role** | Store and use each merchant’s numbers and access tokens **only for messaging** |
| **Multi-merchant** | Supported — full tenant isolation |
| **Multiple numbers** | Supported per tenant |
| **Reply number selection** | Supported — agent can switch which connected number sends the reply |
| **API** | WhatsApp Cloud API (Graph API) |
| **Not used** | Unofficial QR scanners, WhatsApp Web bridges, third-party unofficial APIs |

---

## 3. Architecture decision: Hybrid central + tenant DB

### Confirmed hybrid architecture

Meta delivers webhooks to **one global HTTPS endpoint** before the application knows which tenant owns the `phone_number_id`. The system therefore splits storage:

#### Central DB stores

| Table / data | Purpose |
|---|---|
| `whatsapp_number_registry` | Maps `phone_number_id` → `tenant_id` + tenant-local number ID |
| `whatsapp_webhook_events` | Raw/minimized webhook payloads, processing status, diagnostics |
| Registry metadata | Connection status, health timestamps, default/active flags (no tokens) |
| Unresolved events | Events where `phone_number_id` is unknown or unmapped |

#### Tenant DB stores (per merchant)

| Table | Purpose |
|---|---|
| `whatsapp_numbers` | Connected numbers + **encrypted access tokens** |
| `whatsapp_contacts` | Customer contact records |
| `whatsapp_conversations` | Inbox threads, 24h window, assignment |
| `whatsapp_messages` | Inbound/outbound message timeline |
| `whatsapp_templates` | Manually entered approved templates |
| Spatie permissions/roles | Tenant-scoped WhatsApp RBAC |

### Why hybrid?

```
Meta webhook (global URL)
    → store event centrally
    → resolve phone_number_id in central registry
    → tenancy()->initialize($tenant)
    → write conversation/message in that tenant’s DB
```

### Explicit non-goals in this phase

- **No central mirror/index** of conversations or messages
- **Admin inbox is tenant-filtered** — admin must select a tenant; it is not a global cross-tenant inbox
- **Tenant isolation is mandatory** at DB, middleware, service, and policy layers

---

## 4. Tenancy model

| Aspect | Implementation |
|---|---|
| **Package** | `stancl/tenancy` — database-per-tenant |
| **Tenant models** | `protected $connection = 'tenant'` (e.g. `App\Models\Tenant\WhatsApp*`) |
| **Central models** | `CentralConnection` trait or default connection (e.g. `WhatsAppNumberRegistry`, `WhatsAppWebhookEvent`) |
| **Tenant panel** | `/app` — `InitializeTenancyByDomain` middleware bootstraps tenant DB automatically |
| **Webhook routes** | Registered in `routes/web.php` on the **central** app — **not** in `routes/tenant.php`, **no** tenancy middleware |
| **Admin panel** | `/admin` — central DB by default; inbox/templates pages use **tenant selector** + `WhatsAppTenantContextService::initializeForTenant()` before reading tenant data |

### Admin tenant context lifecycle (post-audit fix)

Admin WhatsApp pages (`WhatsAppInboxPage`, `WhatsAppTemplatesPage`):

- Call `initializeForTenant()` when a tenant is selected
- Call `end()` when selection is cleared
- Use Livewire `hydrate()` / `dehydrate()` to re-init per request and clean up after each response
- When switching tenant A → B: `end()` then `initializeForTenant(B)` — no stale context

---

## 5. Filament panels

### Tenant Panel

| Setting | Value |
|---|---|
| **Provider** | `app/Providers/Filament/TenantPanelProvider.php` |
| **Path** | `/app` |
| **Guard** | `tenant` |
| **Purpose** | Merchant manages own WhatsApp numbers, templates, inbox, webhook events |

**Resources / pages (auto-discovered):**

- `WhatsAppNumberResource` — CRUD numbers, set default, send test message
- `WhatsAppTemplateResource` — manual template CRUD + **Sync from Meta** header action
- `WhatsAppContactResource` — contact list, manual add, send to number, open inbox
- `WhatsAppWebhookEventResource` — events filtered to `tenant_id = current tenant`
- `WhatsAppInboxPage` — conversation list + reply UI (supports `?conversation=` deep link)

Tenant users only see data in their own tenant database (enforced by tenancy middleware + queries).

### Admin Panel

| Setting | Value |
|---|---|
| **Provider** | `app/Providers/Filament/AdminPanelProvider.php` |
| **Path** | `/admin` |
| **Guard** | `admin` |
| **Purpose** | Platform support, registry view, diagnostics |

**Resources / pages:**

- `WhatsAppNumberResource` — reads **central registry** (`WhatsAppNumberRegistry`), enable/disable numbers
- `WhatsAppWebhookEventResource` — all central events including unresolved; raw payload column gated by `whatsapp.platform.troubleshoot`
- `WhatsAppInboxPage` — **requires tenant selection** first
- `WhatsAppTemplatesPage` — **requires tenant selection** first; table query only runs when tenancy is initialized; **Sync from Meta** header action

---

## 6. Main features implemented

- Manual WhatsApp number connection (tenant panel)
- Multiple numbers per tenant
- Default number per tenant (`is_default`)
- **Encrypted** `access_token` at rest
- **Masked** token in Filament UI (never shows real token)
- Empty token field on edit preserves existing token
- Sending test messages from number resource
- WhatsApp inbox (tenant + admin-with-tenant-selector)
- Conversation list sorted by `last_message_at`
- Message timeline per conversation
- 24-hour customer service window badge / policy enforcement
- Freeform text replies **inside** 24h window only
- Template sending **outside** 24h window (and anytime policy allows)
- Manual WhatsApp template CRUD
- **Template sync from Meta** (`SyncWhatsAppTemplatesFromMetaAction` + Filament sync button)
- **WhatsApp contacts** — manual CRUD, send to number, inbound auto-upsert
- Template variable placeholders validation
- Message statuses: `pending`, `sent`, `delivered`, `read`, `failed`, `received`
- Status idempotency via monotonic `canTransitionTo()` (no downgrade read → delivered/sent)
- Central webhook raw event storage + post-process redaction
- **Dedicated webhook request logging** (`storage/logs/whatsapp-webhook.log`)
- Unresolved webhook diagnostics (`processing_status = unresolved`)
- Admin tenant-context cleanup (audit fixes)
- **Shared WhatsApp UI styles** (`resources/css/whatsapp-ui.css` via Filament panel assets)
- **Filament inbox computed properties** for send permissions (`canSendMessages`, `canSendTemplates`, `canSwitchReplyNumber`)
- Feature tests (**32 passing**)

---

## 7. WhatsApp 24-hour customer service window

### Rules

1. An **inbound customer message** opens or reopens the window via `OpenCustomerServiceWindowAction`.
2. `last_customer_message_at` is updated to the inbound timestamp.
3. `customer_service_window_expires_at` = inbound timestamp + **24 hours** (`config/whatsapp.php` → `customer_service_window_hours`).
4. **Freeform text** is allowed only while `now() < customer_service_window_expires_at` (`WhatsAppConversation::canSendFreeformReply()`).
5. **Outside the window**, the user must send an **approved template** (`WhatsAppSendingPolicyService` returns `mustUseTemplate: true`).
6. The window is evaluated **per conversation**.

### Conversation identity

Unique key: **`whatsapp_number_id` + `customer_phone`**

If the agent **switches reply number** in the inbox (`canSwitchReplyNumber`):

- Requires permission `whatsapp.switch_reply_number` (tenant) or `whatsapp.platform.troubleshoot` (admin)
- UI shows number selector only when **more than one active number** exists
- `FindOrCreateConversationAction` finds/creates a **separate conversation** for `(selected_number, customer_phone)`
- The 24h window is evaluated on **that target conversation**, not the originally selected list item’s conversation

---

## 8. Templates

| Topic | Status |
|---|---|
| Storage | Tenant DB `whatsapp_templates` |
| Manual CRUD | Tenant `WhatsAppTemplateResource` + admin read-only `WhatsAppTemplatesPage` |
| **Sync from Meta** | **Implemented** — `GET /{waba_id}/message_templates` with pagination |
| Sync UI | **Sync Templates from Meta** button on tenant list + admin templates page |
| Sync action | `SyncWhatsAppTemplatesFromMetaAction` — upsert by `(name, language, whatsapp_business_account_id)` |
| Submission to Meta | **Not implemented** |
| Sending | Via `SendWhatsAppTemplateMessageAction` + `WhatsAppCloudApiService::sendTemplate()` |
| Policy | Only `Approved` templates that are not `is_disabled_locally` |
| Outside 24h | Allowed (templates are the correct channel for proactive/re-engagement messaging) |
| Opt-in / consent | **Not implemented** — `WhatsAppSendingPolicyService` has a comment noting future opt-in enforcement for campaigns |

### Sync behaviour

1. User clicks **Sync Templates from Meta** (tenant or admin-with-tenant-selected).
2. For each active unique WABA (via active `whatsapp_numbers` with token):
   - `WhatsAppCloudApiService::fetchAllMessageTemplates()` paginates Graph API
   - `WhatsAppTemplatePayloadMapper` maps Meta fields → local model
   - `updateOrCreate` on `(name, language, whatsapp_business_account_id)`
   - Sets `provider_template_id`, `components`, `variables_schema`, `raw_payload`, `last_synced_at`
3. Notification shows created / updated / skipped counts.

**Filament note:** header actions must resolve sync action via `app(SyncWhatsAppTemplatesFromMetaAction::class)` — Filament passes its own `Action` as the first closure argument, not Laravel DI.

---

## 8b. Contacts

| Topic | Status |
|---|---|
| Table | `whatsapp_contacts` (tenant DB) |
| Fields | `phone` (unique, normalized digits), `profile_name`, `last_message_at` |
| Filament | `WhatsAppContactResource` (tenant panel only) |
| Manual add | Create / edit / delete |
| Inbound sync | `UpsertWhatsAppContactAction` on each inbound webhook message |
| Send to contact | Row action **Send Message** — template or text modal |
| Send to new number | Header action **Send to Number** — no pre-existing contact required |
| Open inbox | Row action links to `WhatsAppInboxPage?conversation={id}` |

### Important: no Meta contacts list API

WhatsApp Cloud API does **not** expose an endpoint to download the merchant’s full contact book. Contacts are built from:

1. Inbound messages (webhook) — automatic
2. Manual entry in Filament — implemented
3. Outbound send (creates/updates contact via `UpsertWhatsAppContactAction`) — implemented

### `webhook_status` on numbers (not contacts)

Column exists on `whatsapp_numbers` / registry for future health diagnostics. It is **not** auto-updated by Meta today and is hidden from the number form UI pending automation.

---

## 9. Webhook flow

### Routes

Registered in `routes/web.php` (central, public, `web` middleware only):

```
GET  /webhooks/meta/whatsapp  → WhatsAppWebhookController@verify
POST /webhooks/meta/whatsapp  → WhatsAppWebhookController@receive
```

CSRF exclusion in `bootstrap/app.php`:

```php
$middleware->validateCsrfTokens(except: [
    'webhooks/meta/whatsapp',
]);
```

### GET — Meta verification

Meta sends dot-notation query parameters. The controller reads **dot params first**, with **underscore fallback** for local testing:

| Meta param | Controller reads |
|---|---|
| `hub.mode` | `query('hub.mode') ?? query('hub_mode')` |
| `hub.verify_token` | `query('hub.verify_token') ?? query('hub_verify_token')` |
| `hub.challenge` | `query('hub.challenge') ?? query('hub_challenge')` |

When `hub.mode=subscribe` and verify token matches `config('whatsapp.webhook_verify_token')` (trimmed before compare), the controller returns the challenge string as plain text `200`.

**Verification and receive attempts are logged** to `storage/logs/whatsapp-webhook.log` via `WhatsAppWebhookRequestLogger` (secrets masked). Configure with `WHATSAPP_WEBHOOK_LOG_CHANNEL=whatsapp-webhook`.

**Note:** PHP/Laravel also maps dotted query keys to underscored keys automatically in some environments; explicit dual-read ensures compatibility.

#### curl example

```bash
curl "https://YOUR-DOMAIN.com/webhooks/meta/whatsapp?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=12345"
```

**Expected response:**

```
12345
```

### POST — Receive message/status events

1. Read raw body and `X-Hub-Signature-256` header.
2. If `META_APP_SECRET` is configured:
   - Invalid/missing signature → **403**, minimal diagnostic `WhatsAppWebhookEvent` (`invalid_signature`), **no job dispatched**
3. If `META_APP_SECRET` is empty and `WHATSAPP_ALLOW_UNSIGNED_WEBHOOKS=false` → **403**, no processing
4. Valid request → create central `WhatsAppWebhookEvent` (`processing_status = pending`, full payload stored initially)
5. Dispatch `ProcessWhatsAppWebhookJob` (queue)
6. Return `200 OK` immediately

### Job processing (`ProcessWhatsAppWebhookJob`)

1. Load central event
2. For each `entry.changes[].value`:
   - Read `metadata.phone_number_id`
   - Resolve tenant via `WhatsAppWebhookResolver` → central `whatsapp_number_registry`
   - If not found → mark event `unresolved`, continue
   - `$tenant->run()`:
     - Load tenant `WhatsAppNumber` by registry’s `tenant_whatsapp_number_id`
     - `ProcessInboundMessageAction` for each `messages[]`
     - `ProcessMessageStatusAction` for each `statuses[]`
   - Update event: `tenant_id`, `processed`, redact payload per `webhook_payload_retention`

**Critical:** `tenant_id` from the webhook JSON payload is **never trusted**. Only registry lookup by `phone_number_id` is authoritative.

---

## 10. Sending flow

### Graph API endpoint (all outbound messages)

Every outbound message uses the same Meta endpoint:

```
POST https://graph.facebook.com/{version}/{phone_number_id}/messages
Authorization: Bearer {access_token}
Content-Type: application/json
```

Implemented in `WhatsAppCloudApiService::post()`.

#### Template message example (curl)

```bash
curl -X POST "https://graph.facebook.com/v25.0/{phone_number_id}/messages" \
  -H "Authorization: Bearer {access_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "messaging_product": "whatsapp",
    "to": "201006960579",
    "type": "template",
    "template": {
      "name": "hello_world",
      "language": { "code": "en_US" }
    }
  }'
```

This is equivalent to `WhatsAppCloudApiService::sendTemplate()` → `SendWhatsAppTemplateMessageAction`.

#### Text message (inside 24h window)

Same endpoint with `"type": "text"` and `"text": { "body": "..." }` — see `sendText()`.

### Application flow

1. User opens inbox, contacts, or number test action and selects recipient.
2. System resolves **reply number** (`replyNumberId` or conversation’s default number).
3. If reply number differs from list conversation’s number → `FindOrCreateConversationAction` for `(number, customer_phone)`.
4. `WhatsAppSendingPolicyService` checks:
   - User permission (tenant or admin guard)
   - Number `is_active` and `status = active`
   - Conversation belongs to selected number
   - Rate limit (`whatsapp.send_rate_limit`)
   - For text: 24h window open
   - For template: template approved and not locally disabled
5. `SendWhatsAppTextMessageAction` or `SendWhatsAppTemplateMessageAction`:
   - Create local `WhatsAppMessage` (`pending`)
   - Call `WhatsAppCloudApiService` (Graph API)
   - On success: save `provider_message_id`, set `sent`, update conversation preview/timestamps, sync registry
   - On failure: mark `failed`, store safe error code/message, optionally set number `reconnect_required`
6. Meta status webhooks update `sent` → `delivered` → `read` (monotonic; no downgrades)

---

## 11. Security

| Control | Implementation |
|---|---|
| Token encryption | `WhatsAppNumber` cast: `'access_token' => 'encrypted'` |
| Hidden from arrays | `WhatsAppNumber::$hidden` includes `access_token` |
| UI masking | Filament password field + `masked_access_token` placeholder on edit |
| Edit preserves token | `EditWhatsAppNumber::mutateFormDataBeforeSave()` unsets blank token |
| No token logging | `WhatsAppCloudApiService` logs status/code only; job logs event ID + exception message |
| Webhook signature | `WhatsAppWebhookSignatureVerifier` HMAC SHA-256 when secret configured |
| Tenant resolution | Registry lookup by `phone_number_id` only — never trust payload `tenant_id` |
| Raw payload visibility | Admin column visible only with `whatsapp.platform.troubleshoot` |
| Tenant isolation | Tenant webhook resource: `where('tenant_id', tenant()->getTenantKey())` |
| Admin inbox/templates | Require tenant selection; no tenant DB queries before init |
| Central registry | Stores metadata only — **no access tokens** |

---

## 12. Permissions

### Admin permissions (`guard: admin`)

Defined in `app/Helper/PermissionsArray.php`:

| Key | Purpose |
|---|---|
| `whatsapp.platform.view_all_numbers` | View central number registry |
| `whatsapp.platform.manage_all_numbers` | Enable/disable numbers in registry |
| `whatsapp.platform.view_all_conversations` | Access admin inbox (with tenant selector) |
| `whatsapp.platform.view_all_messages` | Reserved for future message-level admin views |
| `whatsapp.platform.view_all_templates` | Access admin templates page |
| `whatsapp.platform.manage_all_templates` | Future admin template management |
| `whatsapp.platform.view_webhook_events` | View central webhook events |
| `whatsapp.platform.manage_webhook_events` | Future webhook management |
| `whatsapp.platform.troubleshoot` | View raw payloads, send from admin inbox, switch reply number |
| `whatsapp.platform.send_test_messages` | Mapped to troubleshoot for admin send actions |

### Development permission bypass (active)

While the module is still under active development, `BYPASS_PERMISSIONS=true` in `.env` (default when `APP_ENV` ≠ `production`) bypasses all `Gate` / `$user->can()` checks for authenticated users.

**Convention during development:** do not add new permission keys or `visible()` gates on new features until pre-launch hardening.

Admin `id === 1` still bypasses all checks via `Gate::before` when `BYPASS_PERMISSIONS=false`.

### Tenant permissions (`guard: tenant`)

Defined in `app/Helper/TenantPermissionsArray.php`:

| Key | Purpose |
|---|---|
| `whatsapp.view_numbers` | List numbers |
| `whatsapp.manage_numbers` | Create/edit/delete numbers |
| `whatsapp.view_inbox` | Open inbox |
| `whatsapp.send_messages` | Send freeform text (inside 24h) |
| `whatsapp.switch_reply_number` | Change reply number in inbox |
| `whatsapp.view_templates` | List templates |
| `whatsapp.manage_templates` | CRUD templates |
| `whatsapp.send_template_messages` | Send templates |
| `whatsapp.view_webhook_events` | View tenant-filtered central events |

### Syncing permissions for existing tenants

```bash
php artisan tenants:sync-permissions
```

Runs `StoreTenantPermissionsArray()` and `setupStoreAdminRole()` inside each tenant DB, re-assigning the Store Admin role to the first tenant user.

Use `--migrate` to run tenant migrations first:

```bash
php artisan tenants:sync-permissions --migrate
```

---

## 13. Files created/changed

### Config

- `config/whatsapp.php`

### Enums (`app/WhatsApp/Enums/`)

- `WhatsAppConnectionStatus.php`
- `WhatsAppConversationStatus.php`
- `WhatsAppMessageDirection.php`
- `WhatsAppMessageSenderType.php`
- `WhatsAppMessageStatus.php`
- `WhatsAppMessageType.php`
- `WhatsAppTemplateCategory.php`
- `WhatsAppTemplateStatus.php`
- `WhatsAppWebhookProcessingStatus.php`

### Models

**Central:**

- `app/Models/WhatsAppNumberRegistry.php`
- `app/Models/WhatsAppWebhookEvent.php`

**Tenant:**

- `app/Models/Tenant/WhatsAppNumber.php`
- `app/Models/Tenant/WhatsAppContact.php`
- `app/Models/Tenant/WhatsAppConversation.php`
- `app/Models/Tenant/WhatsAppMessage.php`
- `app/Models/Tenant/WhatsAppTemplate.php`

### Migrations

**Central:**

- `database/migrations/2026_07_09_100001_create_whatsapp_number_registry_table.php`
- `database/migrations/2026_07_09_100002_create_whatsapp_webhook_events_table.php`

**Tenant:**

- `database/migrations/tenant/2026_07_09_100001_create_permission_tables.php`
- `database/migrations/tenant/2026_07_09_100002_add_group_name_and_display_name_to_permissions_table.php`
- `database/migrations/tenant/2026_07_09_100003_create_whatsapp_numbers_table.php`
- `database/migrations/tenant/2026_07_09_100004_create_whatsapp_contacts_table.php`
- `database/migrations/tenant/2026_07_09_100005_create_whatsapp_conversations_table.php`
- `database/migrations/tenant/2026_07_09_100006_create_whatsapp_templates_table.php`
- `database/migrations/tenant/2026_07_09_100007_create_whatsapp_messages_table.php`

### Actions (`app/WhatsApp/Actions/`)

- `FindOrCreateConversationAction.php`
- `OpenCustomerServiceWindowAction.php`
- `ProcessInboundMessageAction.php`
- `ProcessMessageStatusAction.php`
- `SendWhatsAppTextMessageAction.php`
- `SendWhatsAppTemplateMessageAction.php`
- `SyncWhatsAppNumberRegistryAction.php`
- `SyncWhatsAppNumberStatusAction.php`
- `SyncWhatsAppTemplatesFromMetaAction.php`
- `UpsertWhatsAppContactAction.php`

### Services (`app/WhatsApp/Services/`)

- `WhatsAppCloudApiService.php` — `sendText`, `sendTemplate`, `fetchAllMessageTemplates`, `healthCheck`
- `WhatsAppSendingPolicyService.php`
- `WhatsAppTemplatePayloadMapper.php`
- `WhatsAppTenantContextService.php`
- `WhatsAppTemplateVariableValidator.php`
- `WhatsAppWebhookPayloadRedactor.php`
- `WhatsAppWebhookRequestLogger.php`
- `WhatsAppWebhookResolver.php`
- `WhatsAppWebhookSignatureVerifier.php`

### Jobs

- `app/WhatsApp/Jobs/ProcessWhatsAppWebhookJob.php`

### DTOs

- `app/WhatsApp/DTOs/SendTextMessageData.php`
- `app/WhatsApp/DTOs/SendTemplateMessageData.php`
- `app/WhatsApp/DTOs/SendingPolicyResult.php`
- `app/WhatsApp/DTOs/SyncWhatsAppTemplatesResult.php`

### Events

- `app/WhatsApp/Events/WhatsAppConversationCreated.php`
- `app/WhatsApp/Events/WhatsAppMessageFailed.php`
- `app/WhatsApp/Events/WhatsAppMessageReceived.php`
- `app/WhatsApp/Events/WhatsAppMessageSent.php`

### Controllers

- `app/Http/Controllers/WhatsAppWebhookController.php`

### Observers

- `app/Observers/Tenant/WhatsAppNumberObserver.php` (registered in `AppServiceProvider`)

### Routes / bootstrap

- `routes/web.php` — webhook routes
- `bootstrap/app.php` — CSRF exception

### Filament — Tenant panel

- `app/Filament/Tenant/Resources/WhatsAppNumbers/WhatsAppNumberResource.php` + Pages
- `app/Filament/Tenant/Resources/WhatsAppTemplates/WhatsAppTemplateResource.php` + Pages
- `app/Filament/Tenant/Resources/WhatsAppContacts/WhatsAppContactResource.php` + Pages
- `app/Filament/Tenant/Resources/WhatsAppWebhookEvents/WhatsAppWebhookEventResource.php` + Pages
- `app/Filament/Tenant/Pages/WhatsAppInboxPage.php`

### Filament — Admin panel

- `app/Filament/Resources/WhatsAppNumbers/WhatsAppNumberResource.php` + Pages
- `app/Filament/Resources/WhatsAppWebhookEvents/WhatsAppWebhookEventResource.php` + Pages
- `app/Filament/Pages/WhatsAppInboxPage.php`
- `app/Filament/Pages/WhatsAppTemplatesPage.php`

### Filament — Shared

- `app/Filament/Shared/WhatsApp/Concerns/ChecksWhatsAppPermissions.php`
- `app/Filament/Shared/WhatsApp/Concerns/InteractsWithWhatsAppInbox.php`
- `app/Filament/Shared/WhatsApp/Actions/SyncWhatsAppTemplatesAction.php`
- `app/Filament/Shared/WhatsApp/Actions/SendWhatsAppMessageFilamentAction.php`
- `app/Filament/Shared/WhatsApp/Schemas/WhatsAppNumberForm.php`
- `app/Filament/Shared/WhatsApp/Schemas/WhatsAppTemplateForm.php`
- `app/Filament/Shared/WhatsApp/Schemas/WhatsAppContactForm.php`
- `app/Filament/Shared/WhatsApp/Tables/WhatsAppNumbersTable.php`
- `app/Filament/Shared/WhatsApp/Tables/WhatsAppTemplatesTable.php`
- `app/Filament/Shared/WhatsApp/Tables/WhatsAppContactsTable.php`

### Views

- `resources/views/filament/shared/whatsapp/inbox.blade.php`
- `resources/views/filament/shared/whatsapp/inbox-admin.blade.php`
- `resources/views/filament/shared/whatsapp/tenant-selector.blade.php`
- `resources/views/filament/tenant/pages/whatsapp-inbox.blade.php`
- `resources/views/filament/pages/whatsapp-templates.blade.php`

### Styles (Filament panel assets)

- `resources/css/filament-custom.css`
- `resources/css/whatsapp-ui.css`

### Permissions / helpers / commands

- `app/Helper/PermissionsArray.php` (extended with `whatsapp.platform.*`)
- `app/Helper/TenantPermissionsArray.php`
- `app/Console/Commands/SyncTenantPermissionsCommand.php`

### Translations

- `lang/en/dashboard.php` — WhatsApp keys
- `lang/ar/dashboard.php` — WhatsApp keys

### Tests (`tests/Feature/WhatsApp/` + `tests/Unit/WhatsApp/`)

- `WhatsAppTestCase.php`
- `WebhookVerificationTest.php`
- `InboundWebhookTest.php`
- `StatusWebhookTest.php`
- `CustomerServiceWindowTest.php`
- `TenantIsolationTest.php`
- `AdminWhatsAppTenantContextTest.php`
- `ReplyNumberConversationTest.php`
- `SyncWhatsAppTemplatesTest.php`
- `WhatsAppContactTest.php`
- `tests/Unit/WhatsApp/WhatsAppTemplatePayloadMapperTest.php`

### Auth / panel routing (tenant login fix)

- `app/Support/FilamentPanelResolver.php`
- `app/Http/Responses/Filament/PanelLoginResponse.php`
- `app/Filament/Auth/Login.php`
- `app/Http/Middleware/TenantAuthenticateSession.php`
- `tests/Feature/Auth/FilamentPanelResolverTest.php`

### Deployment

- `.github/workflows/deploy.yml` — see [`docs/deployment-cwp.md`](deployment-cwp.md)

### Latest patch files (post-freeze)

- `app/Filament/Pages/WhatsAppInboxPage.php` — tenant context cleanup, hydrate/dehydrate
- `app/Filament/Pages/WhatsAppTemplatesPage.php` — tenant context cleanup, lazy `getTableQuery()`, template sync action
- `app/Http/Controllers/WhatsAppWebhookController.php` — dotted webhook verify params, trim tokens, request logging
- `app/WhatsApp/Services/WhatsAppWebhookRequestLogger.php` — dedicated webhook log channel
- `config/logging.php` — `whatsapp-webhook` daily log channel
- `app/Filament/Shared/WhatsApp/Concerns/InteractsWithWhatsAppInbox.php` — `#[Computed]` send permission properties
- `config/tenancy.php` — tenant DB prefix `technomasrsystem_tenant`
- `config/app.php` — `bypass_permissions` / `BYPASS_PERMISSIONS`

---

## 14. Environment variables

| Variable | Default | Purpose |
|---|---|---|
| `WHATSAPP_GRAPH_API_VERSION` | `v21.0` | Graph API version segment in URLs |
| `WHATSAPP_WEBHOOK_VERIFY_TOKEN` | — | Must match Meta App Dashboard verify token |
| `META_APP_SECRET` | — | App Secret for `X-Hub-Signature-256` validation |
| `WHATSAPP_ALLOW_UNSIGNED_WEBHOOKS` | `false` | Allow POST without signature when secret empty (local dev only) |
| `WHATSAPP_DEFAULT_LOCALE` | `ar` | Default locale for platform |
| `WHATSAPP_REQUEST_TIMEOUT` | `30` | HTTP timeout (seconds) for Graph API calls |
| `WHATSAPP_LOG_CHANNEL` | `stack` | Log channel for WhatsApp warnings/errors |
| `WHATSAPP_WEBHOOK_LOG_CHANNEL` | `whatsapp-webhook` | Dedicated webhook verification/receive log |
| `WHATSAPP_SEND_RATE_LIMIT` | `30` | Max sends per minute per tenant/user |
| `WHATSAPP_WEBHOOK_PAYLOAD_RETENTION` | `minimized` | `full` / `minimized` / `metadata` — central payload redaction after processing |
| `BYPASS_PERMISSIONS` | `true` when `APP_ENV` ≠ `production` | Skip permission checks during active development |

Configured in `config/whatsapp.php`. Also documented in `.env.example`.

### Production warnings

- **`WHATSAPP_ALLOW_UNSIGNED_WEBHOOKS` must be `false` in production.**
- **`META_APP_SECRET` should be set in production** so all webhook POSTs are signature-verified.
- **`WHATSAPP_WEBHOOK_VERIFY_TOKEN` must exactly match** the value in the Meta App Dashboard.
- **Never commit real tokens or secrets** to version control.

### Queue connection (staging vs production)

| Environment | `QUEUE_CONNECTION` | Notes |
|---|---|---|
| **Local / staging (current)** | `sync` | Supported intentionally. Jobs (including `ProcessWhatsAppWebhookJob`) run inline in the HTTP request — **no queue worker required**. |
| **Production (recommended later)** | `database` or `redis` | Run a supervised worker (`php artisan queue:work`) so webhooks return 200 quickly and heavy work is off the request. |

Do **not** switch production to `sync` long-term under real Meta traffic.

### Graph API version

- Code/config default and `.env.example`: `WHATSAPP_GRAPH_API_VERSION=v21.0`
- Meta’s latest Graph API as of Feb 2026 is **`v25.0`** ([changelog](https://developers.facebook.com/docs/graph-api/changelog/)). WhatsApp Cloud API docs also show `v25.0` URLs.
- **Recommendation:** update the server `.env` to `WHATSAPP_GRAPH_API_VERSION=v25.0` when convenient (env-only; no code change required). Keep `v21.0` only if a specific Meta app constraint requires it.

---

## 15. Setup / deployment commands

### Fresh / standard deployment sequence

```bash
php artisan migrate
php artisan tenants:sync-permissions --migrate
php artisan config:clear
php artisan optimize:clear
php artisan test
```

**Do not** run `db:seed` on every production deploy — `AdminSeeder` resets the super admin password.

For **first install only**:

```bash
php artisan db:seed --class=AdminSeeder
```

### CWP production (GitHub Actions)

See [`docs/deployment-cwp.md`](deployment-cwp.md). Workflow runs on push to `main` via SSH with PHP 8.3 alt path.

### When to use `--migrate` on permission sync

For **existing tenants** after pulling new tenant migrations (e.g. WhatsApp tables or permission schema):

```bash
php artisan tenants:sync-permissions --migrate
```

This runs `tenants:migrate --force` first, then syncs permissions and Store Admin role per tenant.

### Pre-production warning

**Before production deployment, review all migrations and take database backups.** Tenant migrations create new tables and permission schema; central migrations add registry and webhook tables.

---

## 16. How to manually connect a WhatsApp number

1. Log in to the **Tenant Panel** at `/app` (tenant domain).
2. Open **WhatsApp → Numbers**.
3. Click **Create** and fill required fields:
   - **Display phone number** — human-readable label
   - **Phone number ID** — from Meta WhatsApp Manager (used for webhook routing)
   - **WhatsApp Business Account ID (WABA ID)**
   - **Access token** — long-lived token with messaging permissions
4. Set **status** / **is_active** / **is_default** as needed.
5. Save.

**What happens at runtime:**

- Token is **encrypted** in tenant `whatsapp_numbers` table
- `WhatsAppNumberObserver` calls `SyncWhatsAppNumberRegistryAction`
- Central `whatsapp_number_registry` row is created/updated with **metadata only** (no token)
- Future webhooks for that `phone_number_id` route to this tenant

---

## 17. How to configure Meta webhook

1. In **Meta App Dashboard** → WhatsApp → Configuration:
   - **Callback URL:** `https://YOUR-DOMAIN.com/webhooks/meta/whatsapp`
   - **Verify token:** same value as `WHATSAPP_WEBHOOK_VERIFY_TOKEN` in `.env`
2. Subscribe to WhatsApp webhook fields (at minimum `messages` for inbound + statuses).
3. Ensure:
   - **HTTPS** is valid and publicly reachable
   - Route is **not** behind admin/tenant auth
   - `META_APP_SECRET` is set in production `.env`
   - Staging may use `QUEUE_CONNECTION=sync` (no worker). Production should use `database` or `redis` + supervisor (`php artisan queue:work`)

4. Click **Verify and save** in Meta — Meta sends `GET` with `hub.mode=subscribe`.

---

## 18. Testing checklist

### Automated

```bash
php artisan test
php artisan route:list --path=webhooks
```

Expected routes:

```
GET|HEAD  webhooks/meta/whatsapp
POST      webhooks/meta/whatsapp
```

### Manual / staging

- [ ] curl GET verification with **dotted** Meta params returns challenge
- [ ] Send test message from Tenant → Numbers → Send test
- [ ] Send inbound WhatsApp message to connected number from a real phone
- [ ] Confirm central `whatsapp_webhook_events` row created
- [ ] Confirm tenant `whatsapp_conversations` + `whatsapp_messages` created
- [ ] Confirm 24h window opens (`customer_service_window_expires_at` set)
- [ ] Confirm freeform reply works inside 24h
- [ ] Confirm freeform text blocked outside 24h (policy error)
- [ ] Confirm approved template sends outside 24h
- [ ] **Sync templates from Meta** — tenant Templates page
- [ ] **Add contact manually** and send template from Contacts page
- [ ] **Send to Number** from Contacts header without pre-existing contact
- [ ] Check `storage/logs/whatsapp-webhook.log` after Meta verify attempt
- [ ] Confirm status updates: sent → delivered → read (no downgrades)
- [ ] Admin inbox: select tenant, view conversations, clear tenant — no errors
- [ ] Admin templates: select tenant, view templates

---

## 19. Strict audit and fixes applied

After the full module was implemented across all planned phases, implementation was **frozen** and a strict audit was performed.

### Initial audit state

- Core module functional
- Initial test suite: **12/12 passed**
- Findings: admin tenant context leak; webhook verify params relied on PHP underscore normalization; missing tests for edge cases

### Fixes applied (minimal patches only)

1. **`WhatsAppInboxPage`** — clearing `selectedTenantId` now calls `end()`, clears conversation state, returns early without querying tenant DB; `hydrate()`/`dehydrate()` lifecycle cleanup; safe A→B tenant switch via `end()` before `initializeForTenant()`
2. **`WhatsAppTemplatesPage`** — same tenant context cleanup; **`getTableQuery()` returns `null`** until tenant is initialized (fixes `Database connection [tenant] not configured` on admin page load)
3. **`WhatsAppWebhookController`** — explicit `hub.mode` / `hub.verify_token` / `hub.challenge` with underscore fallback
4. **Tests added/extended:**
   - `AdminWhatsAppTenantContextTest` — context cleanup and switching
   - `WebhookVerificationTest` — dotted Meta params + underscore fallback
   - `StatusWebhookTest` — read does not downgrade to delivered/sent
   - `ReplyNumberConversationTest` — separate conversation per reply number

### Post-freeze enhancements (July 2026)

5. **Webhook request logging** — `WhatsAppWebhookRequestLogger`, verify token trim, dedicated log file
6. **Template sync from Meta** — `SyncWhatsAppTemplatesFromMetaAction`, Filament sync button, mapper + tests
7. **Contacts Filament resource** — manual CRUD, send to number, inbox deep link
8. **Shared WhatsApp UI CSS** — `whatsapp-ui.css` registered in both Filament panels
9. **Filament inbox fixes** — `#[Computed]` permission properties; tenant login panel resolver
10. **CWP deploy workflow** — `tenants:sync-permissions --migrate`, no `db:seed`, `--no-dev`
11. **Development permission bypass** — `BYPASS_PERMISSIONS` config flag
12. **Tenant DB naming** — prefix `technomasrsystem_tenant` for CWP

### Final test result

**32/32 tests passing** (historical post-freeze count; current suite is **36/36**)

---

## 20. Current limitations / not implemented yet

- **API Only Embedded Signup** — **code-complete (Phases A–D)**; **real-number staging E2E pending** (see §21)
- **Coexistence** onboarding — **not implemented** (later phase; do not start yet)
- Template **creation/submission to Meta** not implemented
- Template **sync from Meta** — **implemented** (list/upsert only; no create via API)
- **Meta contacts book sync** not possible — no Graph API; contacts built from inbound + manual entry
- `webhook_status` on numbers — set to `subscribed` after Phase D `subscribed_apps` success; not a live Meta health stream
- **Media upload/download** beyond storing inbound metadata is not complete
- **Campaigns / bulk sending** not implemented
- **Opt-in / consent management** not implemented
- **Order-status WhatsApp notifications** — **postponed** (after Onboarding + Orders); see §21
- **Messenger** — separate channel (see `docs/messenger-messaging-module.md`); out of scope for WhatsApp onboarding
- **Instagram** messaging not implemented
- **Central global conversation search/index** not implemented
- **Platform billing** not implemented (billing is customer-direct to Meta)

---

## 21. Future roadmap

### WhatsApp Onboarding / Connection Methods

**Status:** Phases **A–D** are **code-complete** (API Only Embedded Signup → token exchange → WABA `subscribed_apps` → phone metadata → tenant activation). Automated tests pass. **Real-number staging E2E is pending** until a spare WhatsApp number suitable for Cloud API is available. **Coexistence remains a later phase** (Phase E) — do not start it yet.

**Meta gates (external):**
- Business Verification: **approved**
- Embedded Signup Configuration ID: `1760158035346145` (via `WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID`)

**Architectural rule — central domain only:**
- Embedded Signup JavaScript / Meta Allowed Domains must use the **central** host only: `online-store.technomasrsystems.com`
- Do **not** require every tenant subdomain (`store1…`, `client1…`) in Meta Allowed Domains
- Tenant panel starts onboarding → redirects to central `/whatsapp/onboarding/*` with a **signed** state containing `tenant_id`
- After the Meta flow, return the merchant to the tenant URL from signed `return_url`
- Never trust raw `tenant_id` from query/body without signed state validation

**Purpose:** Finish WhatsApp as a standalone CRM + messaging module. Each tenant chooses a connection method:

1. **API Only** — Embedded Signup + Cloud API only (**Phases A–D code-complete; real E2E pending**)
2. **WhatsApp Business App + Cloud API Coexistence** — **later phase** (not started)

**Manual connection remains fully supported** for admins/developers (staging and production). Existing Filament number CRUD is unchanged.

#### Real-number E2E — important warnings

- Real Embedded Signup E2E requires a **spare** WhatsApp number that can be registered / used with **Cloud API**.
- **Do not** run API Only Embedded Signup on an important WhatsApp Business App number that you intend to keep for **Coexistence later**. API Only and Coexistence are different connection paths; mistaking an active Business App number for API Only testing can complicate or block later Coexistence use.
- Prefer a disposable / staging-only number for the first live validation.
- Until that E2E passes, treat API Only Embedded Signup as **beta / code-complete pending real-number validation** — not production-certified.

#### Phase A delivered

| Item | Detail |
|---|---|
| Enums | `WhatsAppConnectionMethod`, `WhatsAppOnboardingStatus`, `WhatsAppTokenSource` |
| Tenant columns | `connection_method`, `onboarding_status`, `coexistence_enabled`, `business_app_number`, `token_source`, `last_onboarding_error`, `connected_at`, `disconnected_at`, `reconnect_required_at` |
| Backfill | Existing rows → `manual_api_only` / `manual` / `completed` (or `disconnected` if inactive) / `connected_at ≈ created_at` |
| Central registry mirror | Non-sensitive only: `connection_method`, `onboarding_status`, `coexistence_enabled` (no tokens) |
| Defaults | New manual numbers default to completed onboarding + manual token source |

#### Phase B delivered

| Item | Detail |
|---|---|
| Tenant entry | Filament page `ConnectWhatsAppPage` + header action on WhatsApp Numbers |
| Method UX | Manual (existing create) · API Only Embedded Signup (central) · Coexistence (gated / coming soon) |
| Central routes | `GET /whatsapp/onboarding/{start,callback,status}` on central domain middleware |
| Signed state | Encrypted payload: `tenant_id`, `user_id`, `connection_method`, `nonce`, issued/expiry, `return_url` |
| Config | `META_APP_ID`, `WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID`, `WHATSAPP_EMBEDDED_SIGNUP_CENTRAL_DOMAIN` |

#### Phase C delivered

| Item | Detail |
|---|---|
| Launch UI | Central start page loads Facebook JS SDK + Embedded Signup button (`config_id` + `META_APP_ID`) |
| Capture | Browser posts `code` + session asset IDs to `POST /whatsapp/onboarding/complete` with signed `state` |
| Token exchange | Server-side `GET /{version}/oauth/access_token` using `META_APP_ID` + `META_APP_SECRET` (secret never in browser) |
| Persistence | Central `whatsapp_onboarding_sessions` (encrypted token + metadata). When `phone_number_id` + `waba_id` present → upsert tenant `WhatsAppNumber` (`connection_method=embedded_signup_api_only`, `token_source=embedded_signup`, `onboarding_status=subscribing_webhooks`) |
| Why a session table | `whatsapp_numbers.phone_number_id` / `access_token` / WABA are NOT NULL — mid-flow without a phone cannot create a number row safely |
| Security | Signed state required; raw `tenant_id` rejected; tokens encrypted + hidden; registry never stores tokens; Graph errors logged without secrets |
| Status UI | Success / failed / cancelled / awaiting phone / pending Phase D |
| Explicitly not in C | `subscribed_apps`, full phone listing/import polish, template sync, test send, Coexistence |

#### Phase C.1 delivered (session lifecycle hardening)

| Item | Detail |
|---|---|
| Lifecycle columns | `completed_at`, `failed_at` on `whatsapp_onboarding_sessions` |
| Success | Token exchange success → `completed_at` set (`failed_at` cleared) |
| Failure | Token exchange / client failure → `failed_at` set, token cleared |
| Cancelled | Terminal `status=cancelled` with **`failed_at`** as the terminal timestamp (documented convention) |
| Temporary only | Central sessions are **not** used for operational messaging; sends use tenant `WhatsAppNumber` |
| Cleanup | `php artisan whatsapp:onboarding-sessions:cleanup` deletes completed/failed/cancelled/expired sessions older than retention |
| Retention | `WHATSAPP_ONBOARDING_SESSION_RETENTION_DAYS` (default **7**); `--days=` / `--dry-run` supported |
| Safety | Active non-expired sessions without terminal stamps are **not** deleted |

#### Phase D delivered (WABA subscribe + phone import / activation)

| Item | Detail |
|---|---|
| WABA subscribe | `POST /{version}/{waba_id}/subscribed_apps` via `WhatsAppCloudApiService` + `SubscribeWhatsAppWabaWebhooksAction` |
| Phone import | `GET /{waba_id}/phone_numbers` (+ optional `GET /{phone_number_id}`) via `ConfirmWhatsAppPhoneMetadataAction` |
| Orchestration | `FinalizeWhatsAppEmbeddedSignupAction` runs automatically after Phase C token success when a WABA is present; retry via `POST /whatsapp/onboarding/finalize` |
| Status transitions | `subscribing_webhooks` → `completed` \| `awaiting_phone_selection` \| `failed` |
| Phone selection rules | Prefer Embedded Signup `phone_number_id`; single listed phone auto-select; **multiple without clear selection → `awaiting_phone_selection` (never guess)** |
| Tenant number | Upsert with metadata, `connection_method=embedded_signup_api_only`, `token_source=embedded_signup`, `onboarding_status=completed`, `status=active`, `webhook_status=subscribed`, `connected_at` |
| Registry | Observer sync after number update — **no tokens** |
| Retry | Signed state required; uses session token or tenant number token; subscribe + upsert are idempotent; no duplicate `phone_number_id` rows |
| Failure | Safe `last_error` / `last_onboarding_error`; session token cleared; tenant number token kept for retry; manual numbers untouched |
| Status UX | pending / subscribing webhooks / awaiting phone / completed (with next-step copy) / failed / cancelled + retry button |
| Explicitly not in D | Coexistence, template sync, automated test send, campaigns, Orders, Instagram, Messenger, unified inbox, queue changes |
| Live validation | **Real-number staging E2E still pending** (spare Cloud API number required) |

#### Connection methods

| Value | Meaning |
|---|---|
| `manual_api_only` | Current working path — paste phone_number_id, WABA ID, access token |
| `embedded_signup_api_only` | Embedded Signup, Cloud API only (**Phases C–D**) |
| `embedded_signup_coexistence` | Business App + Cloud API — **later** (gated in UI) |

#### Implementation order

| Phase | Scope |
|---|---|
| ~~A~~ | ~~Additive schema + enums + docs~~ **done** |
| ~~B~~ | ~~Tenant Connect WhatsApp UI + central onboarding skeleton + signed state~~ **done** |
| ~~C~~ | ~~Embedded Signup API Only (JS SDK + code → token + minimal persistence)~~ **done** |
| ~~C.1~~ | ~~Onboarding session lifecycle timestamps + cleanup command~~ **done** |
| ~~D~~ | ~~WABA webhook subscription (`subscribed_apps`) + number import polish + registry sync~~ **done** |
| E | Coexistence onboarding + flags |
| F | Tests + docs polish |

**Out of scope until later phases:** Coexistence flow, Tech Provider extras, campaigns, Orders, Messenger/Instagram changes, queue architecture changes.

**CRM messaging policy (unchanged; UI enforcement later):**

- Cold numbers / closed 24h window → **approved templates only** (template-first UI in a later phase)
- Freeform text only inside an open customer service window
- Do not bypass WhatsApp policy for marketing outreach

#### Staging note

Staging may keep `QUEUE_CONNECTION=sync`. Meta Allowed Domains should list the **central** host only (`online-store.technomasrsystems.com`). Set `META_APP_ID`, `META_APP_SECRET`, and `WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID` for live Embedded Signup.

#### Checklist — when a spare test number becomes available

Use this before calling API Only Embedded Signup “staging E2E passed”:

1. Confirm the number is a **spare / disposable** number suitable for Cloud API (not a production Business App number reserved for Coexistence).
2. Confirm staging env: `META_APP_ID`, `META_APP_SECRET`, `WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID`, webhook verify token + app secret, central domain allowlisted in Meta.
3. From a tenant panel → Connect WhatsApp → **API Only** → complete Embedded Signup on the **central** host.
4. Confirm status page reaches **completed** (or document `awaiting_phone_selection` / `failed` clearly).
5. Confirm tenant `WhatsAppNumber` exists with `connection_method=embedded_signup_api_only`, token present, `onboarding_status=completed`, `webhook_status=subscribed`.
6. Confirm central `whatsapp_number_registry` row exists for that `phone_number_id` and **contains no access token**.
7. Send a **manual** outbound test from the tenant UI (existing CRM send — not automated).
8. Receive an **inbound** customer message and confirm webhook → inbox for that number.
9. Confirm the 24h customer service window opens/closes as expected for that conversation.
10. Only then mark API Only Embedded Signup as **staging E2E passed** in this doc. Keep Coexistence unstarted until intentionally scheduled.

### Postponed — Order-status WhatsApp notifications

**Status:** Plan approved earlier; **postponed** until after Onboarding and until Orders domain exists.

**When resumed:** Utility templates only (`confirmed` / `processing` / `shipped` / `delivered` / `cancelled`). MVP = one status (e.g. shipped) + one template + idempotent log + `SendWhatsAppTemplateMessageAction`.

### Longer-term roadmap (not next)

1. ~~Embedded Signup / Coexistence onboarding~~ — **Phases A–D done (API Only); Coexistence = Phase E**
2. ~~Template sync from Meta~~ — sync **done**; submission to Meta still TODO
3. Media handling
4. Opt-in / consent (before campaigns)
5. Campaigns / bulk messaging
6. Order-status Utility notifications — after Orders + onboarding MVP
7. WhatsApp Flows
8. Messenger / Instagram
9. Central reporting / billing ledger (only if billing model changes)

---
## 22. Operational notes

- The webhook endpoint is **global** — never create one webhook URL per tenant in Meta
- Each tenant may have **multiple numbers**; each `phone_number_id` must be unique in central registry
- Each `(whatsapp_number_id, customer_phone)` pair has its **own conversation and 24h window**
- If webhooks show **`unresolved`** in admin:
  - Check whether `phone_number_id` exists in `whatsapp_number_registry`
  - Confirm tenant number is active and observer synced registry
- If sends fail with auth errors → number may be marked **`reconnect_required`**; merchant must supply a new token
- If webhook verification returns **Forbidden** → check `storage/logs/whatsapp-webhook.log` for `configured_verify_token_set`, `verify_token_matches`, run `php artisan config:clear`
- Queue workers must be running for `ProcessWhatsAppWebhookJob`
- After deploy, run `config:clear` and `optimize:clear` if env values changed

---

## 23. Developer notes

### Where to add future code

| Feature | Suggested location |
|---|---|
| Embedded Signup / OAuth | New `app/WhatsApp/Onboarding/` actions + Filament connect wizard; token still saved via `WhatsAppNumber` model |
| Template sync from Meta | `SyncWhatsAppTemplatesFromMetaAction` — **implemented** |
| Template submission | New action calling Graph API template **create** endpoints |
| Contacts UI | `WhatsAppContactResource` + `SendWhatsAppMessageFilamentAction` — **implemented** |
| Media download/upload | Extend `ProcessInboundMessageAction` + `WhatsAppCloudApiService` media methods |
| Opt-in checks | Extend `WhatsAppSendingPolicyService::canSendTemplate()` before campaign sends |
| Order-status Utility notifications | **Postponed.** After Onboarding + Orders. Thin action wrapping `SendWhatsAppTemplateMessageAction`; Utility-category guard |
| Onboarding / Embedded Signup / Coexistence | **API Only code-complete (A–D); real-number E2E pending.** Phase E = Coexistence (later). `app/WhatsApp/Onboarding/`; keep manual CRUD |

### Rules for maintainers

- **Always use `WhatsAppCloudApiService`** for Graph API calls — do not scatter raw HTTP
- **Always use `WhatsAppSendingPolicyService`** before sending — do not bypass 24h/template rules
- **Never write tenant messages from webhook code without `$tenant->run()` or initialized tenancy**
- **Never put `access_token` in central registry** — tokens stay in tenant DB only
- **Never trust `tenant_id` from webhook payload** — use `WhatsAppWebhookResolver` only
- **Admin pages that read tenant data** must use `WhatsAppTenantContextService` and call `end()` when done
- New tenant permissions go in `TenantPermissionsArray.php` + run `tenants:sync-permissions`
- New admin permissions go in `PermissionsArray.php` + run `StorePermissionsArray()` (e.g. via seeder)

---

## 24. Final status

| Item | Status |
|---|---|
| WhatsApp Messaging module | **Implemented** |
| Hybrid central + tenant architecture | **Implemented** |
| Tenant isolation | **Implemented** |
| Manual Cloud API connection | **Implemented** |
| Webhook verification + receiving | **Implemented** |
| Tenant + Admin Filament UI | **Implemented** |
| Automated tests | **Passing** (full suite; re-run before staging E2E) |
| Template sync from Meta | **Implemented** |
| Contacts management UI | **Implemented** |
| Webhook diagnostic logging | **Implemented** |
| Outbound API request DB logging | **Implemented** |
| Staging end-to-end (manual Cloud API) | **Working** — CRM send, inbound webhook → inbox, 24h window open/close, token refresh after expiry |
| Staging queue | **`QUEUE_CONNECTION=sync`** (intentional; no worker) |
| Production queue | **Recommended later:** `database` or `redis` + supervisor — not required while staging stays on `sync` |
| Manual Cloud API integration | **Complete and stabilized on staging** |
| Onboarding Phase A (schema/enums) | **Done** — manual connection unchanged |
| Onboarding Phase B (connect UI + central skeleton) | **Done** — signed state; central-domain-only |
| Onboarding Phase C (Embedded Signup API Only) | **Done** — JS SDK launch, code→token exchange, encrypted persistence |
| Onboarding Phase C.1 (session lifecycle) | **Done** — `completed_at` / `failed_at` + `whatsapp:onboarding-sessions:cleanup` |
| Onboarding Phase D (WABA subscribe + phone activation) | **Code-complete** — `subscribed_apps`, phone metadata import, finalize/retry, status UX |
| API Only Embedded Signup live E2E | **Pending** — needs spare Cloud API–suitable number; not production-certified yet |
| Next WhatsApp implementation | After real-number E2E: polish as needed. **Coexistence (Phase E) remains later** — not started |
| Order-status notifications | **Postponed** until after Onboarding and Orders domain |
| Messenger | **Separate channel** — out of scope for WhatsApp onboarding work |
| Production readiness | Manual path stabilized on staging. Embedded Signup = **beta / awaiting real-number validation**. Production hardening = queue worker + permissions (`BYPASS_PERMISSIONS=false`) + optional Graph API bump to `v25.0` |

---

## 25. Related channel — Messenger (separate)

Messenger is a **separate CRM channel** and must not share WhatsApp tables, services, or webhook routes.

- Plan and status: [`docs/messenger-messaging-module.md`](messenger-messaging-module.md)
- Status as of 2026-07-12: Manual Messenger **staging E2E passed** (Phases A–F). Phase G (Facebook Login) not started.
- Architecture: same hybrid pattern (central registry + webhook events; tenant operational data), different `page_id` / PSID identity model
- WhatsApp onboarding work must **not** change Messenger code

This WhatsApp document remains the source of truth for WhatsApp only. Do not truncate or merge Messenger details into the sections above.

---

*Document version: reflects WhatsApp Onboarding Phases A–D **code-complete** on 2026-07-13, with **real-number staging E2E still pending**. Coexistence not started. Orders postponed. Stack: Laravel 13, Filament ~5, stancl/tenancy, spatie/laravel-permission.*
