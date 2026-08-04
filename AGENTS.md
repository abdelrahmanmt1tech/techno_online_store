# Techno Online Store

## Stack

- **Laravel 13** / PHP ^8.3 / Filament ~5.0 / Tailwind CSS v4 / Vite
- **stancl/tenancy** — multi-tenant (central DB + per-tenant databases)
- **MySQL** — DB; sessions (`database`), cache (`database`), queues (`sync` dev, `database` prod)
- **Locales**: `['en', 'ar']`. `.env` sets `ar`, `.env.example` sets `en` + SQLite.
- **Real `.env` uses MySQL** — never trust `.env.example` for config truth.

## Commands

| Command | Action |
|---|---|
| `composer run setup` | Fresh install: deps, `.env`, key:generate, migrate, `npm install && npm run build` |
| `composer run dev` | `php artisan serve` + queue + pail + Vite via `npx concurrently` |
| `composer run test` | `config:clear` then `php artisan test` |
| `npm run build` / `npm run dev` | Vite build / dev |
| `./vendor/bin/pint` | Lint (Laravel Pint) |
| `php artisan db:seed --class=AdminSeeder` | Super admin (`admin@gmail.com` / `password`) + syncs permissions |
| `php artisan db:seed --class=HomePageDataSeeder` | Settings, plans, FAQs, categories, themes, blog data |
| `php artisan tenants:sync-permissions` | Sync permissions to all tenant DBs (`--migrate` also runs tenant migrations) |
| `php artisan tenants:sync-credentials` | Push `email`/`password` from `TenantUser` to tenant-db `users` |
| `php artisan hr:mark-absent` | Mark employees absent after schedule end (hourly via scheduler) |
| `php artisan whatsapp:onboarding-sessions:cleanup` | Expire stale WhatsApp onboarding sessions |
| `php artisan test --filter=Erp` | Run only ERP tests |
| `php artisan test --filter=CommerceAndSale` | Run ERP↔commerce stock-isolation test |

## Architecture

- **Three Filament panels**, two auth guards (`admin`, `tenant`), primary Emerald:
 - **Admin** (`/admin`, `authGuard('admin')`, `->default()`) — central management. Discovers `app/Filament/Resources/` and `app/Filament/Pages/`.
 - **Tenant** (`/app`, `authGuard('tenant')`) — per-tenant. Discovers `app/Filament/Tenant/{Resources,Pages}/` **and** `app/Filament/Crm/{Resources,Pages,Widgets}`.
 - **CRM** (`/app/crm`, `authGuard('tenant')`, primary Teal) — peer merchant CRM panel (`CrmPanelProvider`). Discovers `app/Filament/Crm/*`; explicitly registers Client, LeadSource, Supplier (those live under `app/Filament/Tenant/Resources/`). Grown into a full pipeline: Opportunities (+ stages, follow-ups, notes, commissions, payment cycles), Campaigns — plus commission/report pages (`app/Filament/Crm/Pages/`) and `/crm/reports/*/print` routes in `routes/tenant.php`.
- CRM + double-entry accounting port notes: [`docs/crm-accounting-port.md`](docs/crm-accounting-port.md). Accounting UI nav group: `__('erp.nav.accounts')` («حسابات وقيود»).
- **Merchant modules** (per-module subscription; plan packages cancelled as gating model): [`docs/tenant-modules.md`](docs/tenant-modules.md). Gate: `tenant_module_enabled()` / `TenantModuleGate` (always `true` until billing). Auto journal posting only when `tenant_accounting_active()`.
- **Central DB**: `admins`, `tenants`, `domains`, `permissions`, `roles`, sessions/cache/jobs.
- **Per-tenant DBs**: `rwadsolu_tenant_{uuid}` (prefix in `config/tenancy.php`). Created synchronously via `CreateDatabase` → `MigrateDatabase`. `SeedTenantDatabase` (a **queued** job, `ShouldQueue`) is dispatched from `CreateTenant.php` and the API controller, not run in the event pipeline.
- **Tenant migrations**: `database/migrations/tenant/` (non-default, set in `tenancy.migration_parameters`).
- **Auth models**: `App\Models\Admin` (`$guard_name='admin'`, central) and `App\Models\TenantUser` (`$guard_name='tenant'`, `$connection='tenant'`). Both use spatie `HasRoles`.
- **Shared login**: Both panels use `App\Filament\Auth\Login` (custom panel resolver in `app/Support/FilamentPanelResolver.php`).
- **Tenant login flow**: Central-domain form (`/tenant-login`) → short-lived `TenantLoginToken` → redirect to `{tenant}/app/login/{token}`. OTP forgot-password at `/tenant/forgot-password`.
- **CSRF exemptions** in `bootstrap/app.php`: `webhooks/meta/whatsapp` and `webhooks/meta/messenger`.

### Routes

- **Central API** (`routes/api.php`): `GET home`, `GET themes`, `GET categories`, `GET footer`, `POST contact`, `GET terms`, `GET privacy`, `GET blogs`, `GET blogs/categories`, `GET blogs/{slug}`, `GET settings`, `GET countries`, `GET currencies`, `GET packages` (active packages; `?country_id=` **required**, falls back to the `is_default` price when the country has no price), `POST tenants` (accepts a top-level `period` = `monthly`/`yearly` that applies to all packages, plus `packages[]` = `[{package_id, price_id}]`, one price per selected package; `price_id` must belong to its `package_id`), `started_at` optional.
- **Tenant API** (`routes/tenant.php`): Auth (register/verify/login/logout/forgot-password), products, categories, governorates, contacts, cart CRUD, coupon apply, checkout (OTP flow), orders, favorites, profile, reviews, home, pages. **Do not register `GET /` here** — it overwrites the central landing and `PreventAccessFromCentralDomains` returns 404 on central domains.
- **Public web** (`routes/web.php`): Landing (`/` + `/platform`), legal pages, WhatsApp/Messenger webhooks, tenant login, forgot-password OTP flow, WhatsApp/Messenger onboarding (central domain middleware).
- **GitHub Actions** (no test CI): `deploy.yml` runs on `dev` push, `deploy-production.yml` on `main` push / `workflow_dispatch`. Both SSH into the server and deploy there. No tests run in CI.

## Filament Resources

Admin resources under `app/Filament/Resources/`, tenant under `app/Filament/Tenant/Resources/`. Each has `Pages/`, `Schemas/`, `Tables/` subdirectories.

- **Admin resources** (17): Admins, Roles, Tenants, Packages, Categories, Countries, Currencies, WhatsAppNumbers, WhatsAppWebhookEvents, Blogs, BlogCategories, Contacts, Faqs, Tags, Themes, MessengerPages, MessengerWebhookEvents
- **Admin pages** (18): 13 settings pages (General, About, AiServices, Code, ContactUs, Footer, HaveQuestion, Intro, MarketingChannels, PaymentGateways, ShippingCompanies, Statistics, TrainingSupport) + MessagingHealthDashboard, MetaIntegrationsReset, WhatsAppInboxPage, WhatsAppTemplatesPage, MessengerInboxPage
- **Admin widgets**: AdminKpis, TenantsTrend, WhatsAppStatusPie, MessengerStatusPie, WebhookEventsTrend
- **Tenant resources** (57 dirs): store (Categories, Products, Brands, Reviews, Coupons, Customers, Contacts, Governorates, Orders, Pages, TenantUsers, Roles) — messaging (WhatsAppNumbers, WhatsAppTemplates, WhatsAppWebhookEvents, WhatsAppApiRequests, WhatsAppContacts, MessengerPages, MessengerWebhookEvents, MessengerApiRequests) — ERP (Branches, Warehouses, UnitsOfMeasure, InventoryItems, Suppliers, InvoicePayments, InvoicePrintSettings, StockTransactions/{Receipt,Issue,Transfer,Adjustment,Damage}, StockMovements, StockBalances, PurchaseOrders, GoodsReceipts, PurchaseInvoices, PurchaseReturns, Sales, SalesInvoices, SalesReturns) — POS (PosRegisters, PosSettings, PosPaymentMethods, CashDrawers, CashierSessions, CashMovements) — HR (HrEmployees, HrDepartments, HrJobTitles, HrAttendanceSchedules, HrAttendanceRecords, HrAttendanceLocations, HrPayrollPeriods, HrSettings) — CRM/accounting (Clients, LeadSources, AccountTrees, AccountsCenterResource, FinancialPeriods, Operations)
- **Tenant pages** (27): WhatsAppInboxPage, MessengerInboxPage, ConnectWhatsAppPage, ConnectMessengerPage, HomeSectionBuilder, BrowseThemesPage, Dashboard, MySubscriptionsPage, GeneralSettings, FooterSettings, ContactUsSettings, CodeSettings, HrAttendanceSummaryPage, HrPayrollSummaryPage + `Accounting/` report pages (BalanceSheet, ProfitAndLoss, TrialBalance, GeneralLedger, AccountsCentersReport, AccountsCenterDetailsReport, AccountTreeStatementPage, AccountTreeCleanupPage, PartyAccountStatement, AssistantLedger, OpeningEntriesReport, PeriodBalancesSnapshotReport, AccountingSettings)
- **Tenant widgets**: StoreKpis, OrdersTrend, OrderStatusPie + Dashboard Lite widgets (SalesChartWidget, SalesCollectionStatsWidget, LatestSalesWidget, LowStockWidget, PosInventoryStatsWidget, AttendanceTodayWidget, HrAttendanceStatsWidget) — see [`docs/dashboard-lite.md`](docs/dashboard-lite.md)
- **Shared components**: `app/Filament/Shared/` (WhatsApp/, Messenger/, SeoFormSection.php, SeoFormOnelanguageSection.php)
- Navigation labels: `__('dashboard.*')` in `lang/{ar,en}/dashboard.php`. ERP: `__('erp.*')` in `lang/{ar,en}/erp.php`.

## ERP Core (Tenant)

FIFO inventory + purchases/sales/invoices in **tenant DB only**. Docs: [`docs/erp-core-architecture.md`](docs/erp-core-architecture.md), [`docs/erp-invoice-printing.md`](docs/erp-invoice-printing.md).

### Commerce vs ERP stock (critical)

- Store qty (`products.quantity` / `product_variants.quantity`) and ERP (`stock_balances` + FIFO layers) are **separate**.
- Cross-impact only via explicit Actions + `commerce_quantity_adjustments` audit with idempotency keys.
- **Never** use Model Observers / `boot()` to sync stock between systems.
- Selling from ERP does **not** change store qty; selling commerce does **not** change ERP.
- Transfers/damage/adjustments never touch store qty unless the line is explicitly `affects_commerce_quantity`.

### FIFO

`FifoCostingService` + `lockForUpdate()`; consume by `received_at`, `id`; no negative stock; transfers preserve original layer unit costs.

## Permissions

Defined in `app/Helper/PermissionsArray.php` (admin) and `app/Helper/TenantPermissionsArray.php` (tenant). Auto-loaded via `composer.json` `files` array (also loads `app/Helper/SeoHelper.php` and `app/Helper/TenantModuleHelper.php`). Keys follow `{group}.{action}` (e.g., `tenants.view`).

- **Dev bypass**: `BYPASS_PERMISSIONS=true` (default when `APP_ENV !== 'production'`) bypasses all `Gate`/`can()` checks. Do **not** add new permission keys or `can*()` checks until pre-production.
- **Meta Integration Reset**: Gated by `config('meta.integration_reset_enabled')` (env `META_INTEGRATION_RESET_ENABLED`). Any new Meta integration table must register in `MetaIntegrationResetRegistry`; update [`docs/meta-integrations-reset.md`](docs/meta-integrations-reset.md) accordingly.

## Testing

- **PHPUnit** (not Pest) — `tests/Unit/`, `tests/Feature/`.
- **SQLite in-memory** (`:memory:`) with `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `SESSION_DRIVER=array` (see `phpunit.xml`).
- `tests/TestCase.php` extends `Illuminate\Foundation\Testing\TestCase` — no `RefreshDatabase` by default; add trait when needed.
- Unit tests extend `PHPUnit\Framework\TestCase` directly (no Laravel app boot).
- Base test cases: `Feature/WhatsApp/WhatsAppTestCase.php`, `Feature/Messenger/MessengerTestCase.php`, `Feature/Erp/ErpTestCase.php`.

## Code Style

- Laravel Pint, 4-space indent per `.editorconfig`.
- **Filament v5**: `Section` imported from `Filament\Schemas\Components\Section` (not `Filament\Forms\Components`). Applies to both `form()` and `infolist()` schemas.

## Tenant Media

Use `asset('storage/tenant'.tenant('id').'/'.$model->file)` — never `asset('storage/'.$model->file)`. Tenant files are isolated per-tenant; `config/tenancy.php` sets `asset_helper_tenancy => false`.

## Commerce Core (Shared Catalog + Sales Engine + POS Foundation)

Docs: [`docs/commerce-core.md`](docs/commerce-core.md). Work lands on `dev` (older `feature/*` branches merged).

- **Single catalog**: extend existing `products` / variants / categories — do **not** create parallel product tables for ERP/POS.
- **UnifiedSalesEngine** (`App\Services\Commerce\UnifiedSalesEngine`): one path for confirm/invoice/payment/return/suspend across Store/ERP/POS/API channels. Delegates to existing ERP Actions.
- **Store checkout behavior unchanged** in this phase (Orders still created in Checkout controllers).
- **POS foundation**: registers, cashier sessions, cash drawers, payment methods, settings, suspend/resume — backend + Filament admin.
- **POS runtime** ([`docs/pos-runtime.md`](docs/pos-runtime.md)): session lifecycle, immutable cash movements, register guard, receipt sequences, shift X/Z reports.
- **POS interface** ([`docs/pos-interface.md`](docs/pos-interface.md)): Blade + Vue terminal at `/app/pos` (session auth + CSRF + Axios). No SPA / Vue Router / Sanctum. Store checkout remains unwired to `UnifiedSalesEngine`.
- **HR Lite** ([`docs/hr-lite.md`](docs/hr-lite.md)): employees, schedules, geofenced attendance, simple deductions, payroll lite. Independent of Store/POS/ERP stock.
- **Dashboard Lite** ([`docs/dashboard-lite.md`](docs/dashboard-lite.md)): permission-aware Tenant Filament dashboard metrics from existing Sale/InvoicePayment/StockBalance/HR tables (no new DB tables).
- Bundle stock deduction deferred. No observers syncing store qty ↔ ERP FIFO.

## Documentation

| File | Purpose |
|---|---|
| [`docs/crm-accounting-port.md`](docs/crm-accounting-port.md) | CRM + double-entry accounting port |
| [`docs/tenant-modules.md`](docs/tenant-modules.md) | Merchant module gating model (`tenant_module_enabled()`) |
| [`docs/commerce-core.md`](docs/commerce-core.md) | Shared catalog, UnifiedSalesEngine, POS foundation |
| [`docs/pos-runtime.md`](docs/pos-runtime.md) | POS session lifecycle, cash flow, guard, receipts, shift reports |
| [`docs/pos-interface.md`](docs/pos-interface.md) | POS Blade + Vue terminal, routes, checkout/suspend/close flows |
| [`docs/hr-lite.md`](docs/hr-lite.md) | HR Lite: employees, attendance geofence, simple payroll |
| [`docs/dashboard-lite.md`](docs/dashboard-lite.md) | Tenant Dashboard Lite metrics and permissions |
| [`docs/tenant-navigation-sidebar.md`](docs/tenant-navigation-sidebar.md) | Central Tenant sidebar ordering, grouping, and collapsed-navigation behavior |
| [`docs/erp-core-architecture.md`](docs/erp-core-architecture.md) | FIFO inventory, purchases/sales/invoices; commerce↔ERP rules |
| [`docs/erp-invoice-printing.md`](docs/erp-invoice-printing.md) | Browser print-ready invoices, settings singleton, snapshots |
| [`docs/whatsapp-messaging-module.md`](docs/whatsapp-messaging-module.md) | WhatsApp Cloud API module |
| [`docs/messenger-messaging-module.md`](docs/messenger-messaging-module.md) | Messenger module |
| [`docs/messaging-health-dashboard.md`](docs/messaging-health-dashboard.md) | Admin central registry/webhook diagnostics |
| [`docs/meta-integrations-reset.md`](docs/meta-integrations-reset.md) | Destructive wipe of Meta integration data |
| [`docs/tenancy-summary.md`](docs/tenancy-summary.md) | Tenancy architecture |
| [`docs/deployment-cwp.md`](docs/deployment-cwp.md) | Manual CWP shared-hosting deployment notes |
| [`docs/subscriptions-packages-plan.md`](docs/subscriptions-packages-plan.md) | Packages / subscriptions / billing plan |
| [`PLAN.md`](PLAN.md) | Cart/orders/checkout specification (Arabic) |
| [`docs/frontend-api.postman_collection.json`](docs/frontend-api.postman_collection.json) | Public API endpoints |
| [`docs/tenant-create-api.postman_collection.json`](docs/tenant-create-api.postman_collection.json) | Create-tenant / packages API endpoints |
| [`docs/tenant-auth-api.postman_collection.json`](docs/tenant-auth-api.postman_collection.json) | Tenant auth API endpoints |
| [`docs/tenant-orders-api.postman_collection.json`](docs/tenant-orders-api.postman_collection.json) | Tenant orders API endpoints |
| [`docs/favorites-api.postman_collection.json`](docs/favorites-api.postman_collection.json) | Favorites API endpoints |
| [`docs/reviews-api.postman_collection.json`](docs/reviews-api.postman_collection.json) | Reviews API endpoints |

## Gotchas

- **Never** run `migrate:fresh`, `db:wipe`, `migrate:refresh`, or any destructive DB command without asking the user first.
- **Do not set `SESSION_DOMAIN` to a value with a port** (e.g., `localhost:8000`).
- `composer run dev` uses `npx concurrently` (needs Node) and `php artisan pail` (needs `pcntl` — not available on Windows). Run the other 3 processes manually if on Windows.
- Tenant seeding (`SeedTenantDatabase`) is dispatched as a queued job from `CreateTenant.php` and the API controller, not run in the event pipeline.
- Deploy workflows delete `public/css/app/{custom-stylesheet,whatsapp-ui,messaging-health-dashboard,meta-integrations-reset,crm-custom-stylesheet,accounting-reports}.css` and back up `public/.htaccess` before `git checkout`/`reset --hard` to avoid merge conflicts with generated/server files.
- Production deploy (`deploy-production.yml`) additionally runs `CountrySeeder` + `CurrencySeeder` and `tenants:sync-permissions --migrate`.
- `composer.json` `post-autoload-dump` runs `filament:upgrade` — may fail if Filament assets aren't published.
