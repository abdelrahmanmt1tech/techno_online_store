# Techno Online Store

## Stack

- **Laravel 13** / PHP ^8.3 / Filament ~5.0 / Tailwind CSS v4 / Vite
- **stancl/tenancy** — multi-tenant app (central DB + per-tenant databases)
- **MySQL** — DB, sessions (`database`), cache (`database`), queues (`database`)
- **Locale**: Arabic (`ar`) in `.env`. `.env.example` defaults to SQLite/English; actual `.env` uses MySQL/Arabic.

## Commands

| Command | Action |
|---|---|
| `composer run setup` | Fresh install: install deps, create `.env`, key:generate, migrate, `npm install && npm run build` |
| `composer run dev` | Start dev server + queue + logs + Vite (4 concurrent processes via `npx concurrently`) |
| `composer run test` | `config:clear` then `php artisan test` |
| `npm run build` / `npm run dev` | Vite build / dev |
| `./vendor/bin/pint` | Lint (Laravel Pint) — no npm lint script |
| `php artisan db:seed --class=AdminSeeder` | Seed super admin (`admin@gmail.com` / `password`) — also syncs permissions |
| `php artisan tenants:sync-permissions` | Sync permissions to all tenant DBs (add `--migrate` to also run tenant migrations) |

## Architecture

- **Two Filament panels** (separate auth guards, primary color `Emerald`):
  - **Admin panel** (`/admin`, panel ID `admin`, `authGuard('admin')`) — central management. Discovers `app/Filament/Resources/` and `app/Filament/Pages/`.
  - **Tenant panel** (`/app`, panel ID `tenant`, `authGuard('tenant')`) — per-tenant. Discovers `app/Filament/Tenant/{Resources,Pages}/`. Uses `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` + `EnsureTenantIsInitialized`; routes in `routes/tenant.php`.
- **Central DB** — `admins`, `tenants`, `domains`, `permissions`, `roles`, `role_has_permissions`, `model_has_permissions`, sessions/cache/jobs.
- **Per-tenant DBs** — created synchronously. Tenancy event pipeline (`TenancyServiceProvider`) fires `CreateDatabase` → `MigrateDatabase`. `SeedTenantDatabase` called synchronously after pipeline completes.
- **Tenant DB naming**: `technomasrsystem_tenant{tenant_uuid}` (prefix in `config/tenancy.php`).
- **Auth models**: `App\Models\Admin` (`$guard_name = 'admin'`, central DB) and `App\Models\TenantUser` (`$guard_name = 'tenant'`, per-tenant DB). Both use spatie `HasRoles`.
- **Shared Login component**: Both panels use `App\Filament\Auth\Login` (custom panel resolver logic in `app/Support/FilamentPanelResolver.php`).
- **No API routes**. `bootstrap/app.php` has placeholder JSON handling for `api/*`.
- **GitHub Actions**: `.github/workflows/deploy.yml` — deploys on push to `main` via SSH to CWP. Sequence: `composer install --no-dev` → central `migrate` → `tenants:sync-permissions --migrate` → `npm ci && npm run build` → `filament:assets` → `optimize:clear` → `optimize` → `queue:restart`.
- **CSRF exemptions** in `bootstrap/app.php`: `webhooks/meta/whatsapp` and `webhooks/meta/messenger` are excluded from CSRF verification.

## Filament Resources

Admin resources under `app/Filament/Resources/`, tenant resources under `app/Filament/Tenant/Resources/`. Each resource has `Pages/`, `Schemas/`, `Tables/` subdirectories.

Current resources:
- **Admin**: Admins, Roles, Tenants, Plans, Categories, WhatsAppNumbers, WhatsAppWebhookEvents, Blogs, BlogCategories, Contacts, Faqs, Tags, Themes, MessengerPages, MessengerWebhookEvents
- **Tenant**: Categories, Products, WhatsAppContacts, WhatsAppNumbers, WhatsAppTemplates, WhatsAppWebhookEvents, WhatsAppApiRequests, MessengerPages, MessengerWebhookEvents, MessengerApiRequests

Tenant pages (`app/Filament/Tenant/Pages/`): WhatsAppInboxPage, MessengerInboxPage, ConnectWhatsAppPage, ConnectMessengerPage

Admin pages (`app/Filament/Pages/`): 13 settings pages (General, About, AiServices, Code, ContactUs, Footer, HaveQuestion, Intro, MarketingChannels, PaymentGateways, ShippingCompanies, Statistics, TrainingSupport) plus WhatsAppInboxPage, WhatsAppTemplatesPage, MessengerInboxPage

Shared components in `app/Filament/Shared/` (WhatsApp/, Messenger/, SeoFormSection.php).

Navigation labels use `__('dashboard.*')` translations (`lang/{ar,en}/dashboard.php`).

Permissions defined in `app/Helper/PermissionsArray.php` (admin, guard `admin`) and `app/Helper/TenantPermissionsArray.php` (tenant, guard `tenant`). Auto-loaded via `composer.json` `files` array (also loads `app/Helper/SeoHelper.php`). Permission keys follow pattern `{group}.{action}` (e.g., `tenants.view`, `roles-and-permission.destroy`).

**Development mode**: `BYPASS_PERMISSIONS=true` (or any non-`production` `APP_ENV`) bypasses all `Gate`/`$user->can()` checks. The config in `config/app.php` defaults to `true` unless `APP_ENV=production`. Do **not** add new permission keys or `can*()` checks on new features until pre-production.

## Testing

- **PHPUnit** (not Pest) — `tests/Unit/` and `tests/Feature/`.
- Uses **SQLite** (`database/testing.sqlite`) with `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `SESSION_DRIVER=array` (see `phpunit.xml`).
- `tests/TestCase.php` extends `Illuminate\Foundation\Testing\TestCase` (no `RefreshDatabase` by default — add trait when needed).
- Unit tests extend `PHPUnit\Framework\TestCase` directly (no Laravel app boot).

## Code Style

- Laravel Pint for formatting, 4-space indentation per `.editorconfig`.

## Documentation

| Document | Purpose |
|---|---|
| [`docs/whatsapp-messaging-module.md`](docs/whatsapp-messaging-module.md) | WhatsApp Cloud API module. Manual integration complete; onboarding Phase A done; Phase B+ blocked on Meta verification |
| [`docs/messenger-messaging-module.md`](docs/messenger-messaging-module.md) | Messenger module. Phases A–F complete; Phase G (Facebook Login) blocked |
| [`docs/deployment-cwp.md`](docs/deployment-cwp.md) | CWP production deploy sequence and required secrets |
| [`docs/tenancy-summary.md`](docs/tenancy-summary.md) | Tenancy architecture summary |

## Gotchas

- **NEVER run `migrate:fresh`, `db:wipe`, `migrate:refresh`, or any destructive database command without explicitly asking the user first.** These commands destroy all data. Always ask for confirmation before running them.
- **Do not set** `SESSION_DOMAIN` to a value with a port (e.g., `localhost:8000`).
- `composer run dev` uses `npx concurrently` — requires Node.js available.
- `.env.example` defaults to SQLite but actual `.env` uses MySQL. Always check `.env` not `.env.example` for truth.
- Tenant seeding (`SeedTenantDatabase`) and `setupStoreAdminRole()` are invoked from `CreateTenant.php`, not from the tenancy event pipeline.
- The deploy workflow (`deploy.yml`) deletes `public/css/app/custom-stylesheet.css` before `git pull` — this is intentional to avoid merge conflicts with a generated/custom file.
