# Meta Integrations Reset

**Audience:** Platform operators preparing Meta App Review recordings  
**Route:** `/admin/meta-integrations-reset`  
**Class:** `App\Filament\Pages\MetaIntegrationsReset`  
**Service:** `App\Support\MetaReset\MetaIntegrationResetService`  
**Registry:** `App\Support\MetaReset\MetaIntegrationResetRegistry`

## 1. Purpose

Destructive Admin maintenance tool that removes **local** WhatsApp and Messenger integration data from:

1. The central database  
2. Every tenant database  

Use it to restore a clean onboarding state before Meta App Review videos. It is **not** a normal product feature.

## 2. Safety model

Never deletes tenants, domains, admins/users, roles/permissions, products, orders, categories, platform settings, migrations, or `meta_integration_reset_runs` audit rows.

Only tables explicitly listed in `MetaIntegrationResetRegistry` are touched.

No Graph API calls. Tokens are deleted with their rows and never logged or returned in reports.

## 3. Environment flag

```env
META_INTEGRATION_RESET_ENABLED=false
```

Preview and execute throw if the flag is not `true`. Authorized admins can open the page and see a disabled banner when the flag is false.

## 4. Permission

`meta.integrations.reset` (admin guard). Seeded via `PermissionsArray` / AdminSeeder.

`BYPASS_PERMISSIONS` still applies in non-production per project conventions. Backend service also checks authorization independently.

## 5–6. Preview and confirmation

1. Select scope: `all` | `whatsapp` | `messenger` (default: none)  
2. **Preview Reset** (read-only counts; one tenant at a time)  
3. Checkbox + type exactly `RESET META INTEGRATIONS`  
4. Filament danger `wire:confirm` modal  
5. Preview expires after 10 minutes or when scope changes  

## 7–8. Registered tables

### Central — WhatsApp
| Order | Table | Credentials |
|------:|-------|-------------|
| 10 | `whatsapp_webhook_events` | no |
| 20 | `whatsapp_onboarding_sessions` | yes |
| 30 | `whatsapp_number_registry` | no |

### Tenant — WhatsApp
| Order | Table | Credentials |
|------:|-------|-------------|
| 10 | `whatsapp_api_requests` | no |
| 20 | `whatsapp_messages` | no |
| 30 | `whatsapp_conversations` | no |
| 40 | `whatsapp_contacts` | no |
| 50 | `whatsapp_templates` | no |
| 60 | `whatsapp_numbers` | yes |

### Central — Messenger
| Order | Table | Credentials |
|------:|-------|-------------|
| 10 | `messenger_webhook_events` | no |
| 20 | `messenger_onboarding_sessions` | yes |
| 30 | `messenger_page_registry` | no |

### Tenant — Messenger
| Order | Table | Credentials |
|------:|-------|-------------|
| 10 | `messenger_api_requests` | no |
| 20 | `messenger_messages` | no |
| 30 | `messenger_conversations` | no |
| 40 | `messenger_contacts` | no |
| 50 | `messenger_pages` | yes |

Order follows FK dependencies from tenant migrations (children before parents). Deletes use `DB::table()->delete()` (includes soft-deleted parent rows).

## 9. Deliberately excluded

- Shared CRM / store tables (`products`, `orders`, `customers` if any generic table)  
- `tenants`, `domains`, `admins`, `users`, roles/permissions  
- `settings`, migrations  
- `meta_integration_reset_runs` (audit must survive resets)  
- Instagram (not in this Meta App)

## 10. Execution strategy

- Central: one DB transaction  
- Tenants: initialize → one transaction → `finally` end tenancy  
- Not globally atomic across databases  
- Any tenant failure → status `partially_failed`  
- Cache lock `meta-integration-reset` prevents concurrent runs  

## 11. Audit

Table `meta_integration_reset_runs` stores safe counts, status, and redacted errors. Never tokens/payloads/secrets.

## 12. External Meta state

Local reset only. Pages/WABAs may remain subscribed in Meta. Reconnect recreates local registry rows. External unsubscribe is out of scope.

## 13. App Review recording workflow

1. Set `META_INTEGRATION_RESET_ENABLED=true` temporarily  
2. Open `/admin/meta-integrations-reset`  
3. Scope = All Meta integrations  
4. Preview → verify only integration tables  
5. Confirm phrase → execute  
6. Review report  
7. Set flag back to `false`  
8. Reconnect test tenant assets while recording  

## 14. How to add a future Meta integration table

A Meta-related migration is **not complete** until the registry decision is documented.

Checklist:

- Central or tenant?  
- Channel (`whatsapp` / `messenger` / future)?  
- Integration-only vs shared business data?  
- Tokens/credentials?  
- FK delete priority?  
- Required vs optional?  
- Preview counts?  
- Tests + this doc updated?

### Code example

```php
// In MetaIntegrationResetRegistry::all()
new MetaIntegrationResetTable(
    channel: self::SCOPE_WHATSAPP,
    scope: self::DB_TENANT,
    table: 'whatsapp_new_feature_rows',
    priority: 25, // between messages (20) and conversations (30) if needed
    optional: false,
    description: '…',
    reason: '…',
    mayContainCredentials: false,
),
```

## Related

- [`docs/whatsapp-messaging-module.md`](whatsapp-messaging-module.md)  
- [`docs/messenger-messaging-module.md`](messenger-messaging-module.md)  
- [`docs/messaging-health-dashboard.md`](messaging-health-dashboard.md)
