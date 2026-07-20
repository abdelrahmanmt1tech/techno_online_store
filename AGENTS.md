# Techno Online Store

## Stack

- **Laravel 13** / PHP ^8.3 / Filament ~5.0 / Tailwind CSS v4 / Vite
- **stancl/tenancy** — multi-tenant app (central DB + per-tenant databases)
- **MySQL** — DB, sessions (`database`), cache (`database`), queues (`database`)
- **Locale**: English (`en`) in `.env`. Supported locales: `['en', 'ar']`. `.env.example` defaults to SQLite/English.

## Commands

| Command | Action |
|---|---|
| `composer run setup` | Fresh install: install deps, create `.env`, key:generate, migrate, `npm install && npm run build` |
| `composer run dev` | Start dev server + queue + logs + Vite (4 concurrent processes via `npx concurrently`) |
| `composer run test` | `config:clear` then `php artisan test` |
| `npm run build` / `npm run dev` | Vite build / dev |
| `./vendor/bin/pint` | Lint (Laravel Pint) — no npm lint script |
| `php artisan db:seed --class=AdminSeeder` | Seed super admin (`admin@gmail.com` / `password`) — also syncs permissions |
| `php artisan db:seed --class=HomePageDataSeeder` | Seed settings, plans, FAQs, categories, themes, blog categories, tags, blog posts |
| `php artisan tenants:sync-permissions` | Sync permissions to all tenant DBs (add `--migrate` to also run tenant migrations) |

## Architecture

- **Two Filament panels** (separate auth guards, primary color `Emerald`):
  - **Admin panel** (`/admin`, panel ID `admin`, `authGuard('admin')`) — central management. `->default()`. Discovers `app/Filament/Resources/` and `app/Filament/Pages/`. Note: `AdminPanelProvider` calls `->colors()` twice (Amber then Emerald — Emerald wins).
  - **Tenant panel** (`/app`, panel ID `tenant`, `authGuard('tenant')`) — per-tenant. Discovers `app/Filament/Tenant/{Resources,Pages}/`. Uses `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` + `EnsureTenantIsInitialized`; routes in `routes/tenant.php`. `persistentMiddleware([InitializeTenancyByDomain])`. Uses custom `TenantAuthenticateSession` middleware that resolves panel via `FilamentPanelResolver`.
- **Central DB** — `admins`, `tenants`, `domains`, `permissions`, `roles`, `role_has_permissions`, `model_has_permissions`, sessions/cache/jobs.
- **Per-tenant DBs** — created synchronously. Tenancy event pipeline (`TenancyServiceProvider`) fires `CreateDatabase` → `MigrateDatabase`. `SeedTenantDatabase` called synchronously after pipeline completes.
- **Tenant DB naming**: `technomasrsystem_tenant{tenant_uuid}` (prefix in `config/tenancy.php`).
- **Tenant migrations**: live in `database/migrations/tenant/` (non-default path, configured via `tenancy.migration_parameters`).
- **Auth models**: `App\Models\Admin` (`$guard_name = 'admin'`, central DB) and `App\Models\TenantUser` (`$guard_name = 'tenant'`, `$connection = 'tenant'`, per-tenant DB). Both use spatie `HasRoles`.
- **Shared Login component**: Both panels use `App\Filament\Auth\Login` (custom panel resolver logic in `app/Support/FilamentPanelResolver.php`).
- **Tenant login flow**: Central-domain form (`/tenant-login`) authenticates via `TenantLoginController`, creates short-lived token (`TenantLoginToken`), redirects to tenant subdomain (`/app/login/{token}`). Token-based, cross-domain. OTP-based forgot-password flow at `/tenant/forgot-password`.
- **CSRF exemptions** in `bootstrap/app.php`: `webhooks/meta/whatsapp` and `webhooks/meta/messenger` are excluded from CSRF verification.

### Routes

- **Central API** (`routes/api.php`): `GET home`, `GET themes`, `GET categories`, `GET footer`, `POST contact`, `GET terms`, `GET privacy`, `GET blogs`, `GET blogs/categories`, `GET blogs/{slug}`, `GET settings`, `POST tenants`.
- **Tenant API** (`routes/tenant.php`): `GET products`, `GET products/{slug}`, `GET governorates`, cart CRUD, coupon apply/remove, `POST checkout/{token}`, `GET orders/{token}`. Token-based login: `GET /app/login/{token}`.
- **Public web** (`routes/web.php`): Legal pages (`/privacy-policy`, `/terms-of-service`, `/data-deletion`), WhatsApp/Messenger webhooks (GET+POST), tenant login, forgot password (OTP flow), WhatsApp/Messenger onboarding routes (central domain middleware).
- **GitHub Actions**: `.github/workflows/deploy.yml` — deploys on push to `main` via SSH to CWP. Sequence: `composer install --no-dev` → central `migrate` → `tenants:sync-permissions --migrate` → `npm ci && npm run build` → `filament:assets` → `optimize:clear` → `optimize` → `queue:restart`.
- No test CI workflow exists — only deploy. Tests are not automatically run on push.

## Filament Resources

Admin resources under `app/Filament/Resources/`, tenant resources under `app/Filament/Tenant/Resources/`. Each resource has `Pages/`, `Schemas/`, `Tables/` subdirectories.

- **Admin** (15): Admins, Roles, Tenants, Plans, Categories, WhatsAppNumbers, WhatsAppWebhookEvents, Blogs, BlogCategories, Contacts, Faqs, Tags, Themes, MessengerPages, MessengerWebhookEvents
- **Tenant** (13): Categories, Products, Orders, Coupons, Governorates, WhatsAppContacts, WhatsAppNumbers, WhatsAppTemplates, WhatsAppWebhookEvents, WhatsAppApiRequests, MessengerPages, MessengerWebhookEvents, MessengerApiRequests

Tenant pages (`app/Filament/Tenant/Pages/`): WhatsAppInboxPage, MessengerInboxPage, ConnectWhatsAppPage, ConnectMessengerPage

Admin pages (`app/Filament/Pages/`): 13 settings pages (General, About, AiServices, Code, ContactUs, Footer, HaveQuestion, Intro, MarketingChannels, PaymentGateways, ShippingCompanies, Statistics, TrainingSupport) plus WhatsAppInboxPage, WhatsAppTemplatesPage, MessengerInboxPage

Admin widgets (`app/Filament/Widgets/`): AdminKpis, TenantsTrend, TenantSubscriptionStatusPie, WhatsAppStatusPie, MessengerStatusPie, WebhookEventsTrend

Shared components in `app/Filament/Shared/` (WhatsApp/, Messenger/, SeoFormSection.php) with subdirectories: Tables/, Schemas/, Actions/, Concerns/.

Navigation labels use `__('dashboard.*')` translations (`lang/{ar,en}/dashboard.php`).

Permissions defined in `app/Helper/PermissionsArray.php` (admin, guard `admin`) and `app/Helper/TenantPermissionsArray.php` (tenant, guard `tenant`). Auto-loaded via `composer.json` `files` array (also loads `app/Helper/SeoHelper.php`). Permission keys follow pattern `{group}.{action}` (e.g., `tenants.view`, `roles-and-permission.destroy`).

**Development mode**: `BYPASS_PERMISSIONS=true` (or any non-`production` `APP_ENV`) bypasses all `Gate`/`$user->can()` checks. The config in `config/app.php` defaults to `true` unless `APP_ENV=production`. Do **not** add new permission keys or `can*()` checks on new features until pre-production.

## Testing

- **PHPUnit** (not Pest) — `tests/Unit/` and `tests/Feature/`.
- Uses **SQLite in-memory** (`:memory:`) with `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `SESSION_DRIVER=array` (see `phpunit.xml`).
- `tests/TestCase.php` extends `Illuminate\Foundation\Testing\TestCase` (no `RefreshDatabase` by default — add trait when needed).
- Unit tests extend `PHPUnit\Framework\TestCase` directly (no Laravel app boot).
- Base test cases: `Feature/WhatsApp/WhatsAppTestCase.php`, `Feature/Messenger/MessengerTestCase.php`.

## Code Style

- Laravel Pint for formatting, 4-space indentation per `.editorconfig`.

## Documentation

| Document | Purpose |
|---|---|
| [`docs/whatsapp-messaging-module.md`](docs/whatsapp-messaging-module.md) | WhatsApp Cloud API module. Manual integration complete; API Only Embedded Signup (Phases A–D) and Coexistence (Phase E) code-complete — staging E2E postponed pending numbers. Orders notifications postponed. |
| [`docs/messenger-messaging-module.md`](docs/messenger-messaging-module.md) | Messenger module. Phases A–F complete (staging E2E passed). Phase G code-complete (Facebook Login + Page picker + auto webhook subscription); staging E2E pending. |
| [`docs/deployment-cwp.md`](docs/deployment-cwp.md) | CWP production deploy sequence and required secrets |
| [`docs/tenancy-summary.md`](docs/tenancy-summary.md) | Tenancy architecture summary |
| [`docs/frontend-api.postman_collection.json`](docs/frontend-api.postman_collection.json) | Postman collection for the frontend/public API endpoints |

## Code Style

- **Filament v5 imports**: `Section` must be imported from `Filament\Schemas\Components\Section` (not `Filament\Forms\Components`). This applies to both `form()` and `infolist()` schemas.

## API Resources Conventions

- **Tenant media paths**: Always use `asset('storage/tenant'.tenant('id').'/'.$model->file)` for tenant file/image URLs. Never use `asset('storage/'.$model->file)` — tenant files are isolated in per-tenant directories.

## Gotchas

- **NEVER run `migrate:fresh`, `db:wipe`, `migrate:refresh`, or any destructive database command without explicitly asking the user first.** These commands destroy all data. Always ask for confirmation before running them.
- **Do not set** `SESSION_DOMAIN` to a value with a port (e.g., `localhost:8000`).
- `composer run dev` uses `npx concurrently` — requires Node.js available. Also uses `php artisan pail` which requires the `pcntl` PHP extension (not available on Windows — run the 3 other processes manually if needed).
- `.env.example` defaults to SQLite but actual `.env` uses MySQL. Always check `.env` not `.env.example` for truth.
- Tenant seeding (`SeedTenantDatabase`) and `setupStoreAdminRole()` are invoked from `CreateTenant.php`, not from the tenancy event pipeline.
- The deploy workflow (`deploy.yml`) deletes `public/css/app/custom-stylesheet.css` and `public/css/app/whatsapp-ui.css` before `git pull` — intentional to avoid merge conflicts with generated/custom files.
- `composer.json` `post-autoload-dump` runs `filament:upgrade` — this may cause issues if Filament assets aren't published.
