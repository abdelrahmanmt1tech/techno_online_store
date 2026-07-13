# Tenancy Architecture

Built on **[stancl/tenancy v3](https://tenancyforlaravel.com/docs/v3/)** — multi-tenant SaaS with a central DB + per-tenant databases.

## Core Concepts

| Concept | Implementation |
|---|---|
| **Tenant model** | `App\Models\Tenant` extends `Stancl\Tenancy\Database\Models\Tenant` — central DB table `tenants` with custom columns: `id` (UUID), `name`, `email`, `phone`, `is_active`. Uses `SoftDeletes`. |
| **Domain model** | `Stancl\Tenancy\Database\Models\Domain` — central DB table `domains` with `domain` (e.g. `store.example.com`) and `tenant_id`. |
| **Tenant DB naming** | `technomasrsystem_tenant{uuid}` (config `tenancy.database.prefix`). Created synchronously on tenant creation. |
| **ID generator** | UUID (via `Stancl\Tenancy\UUIDGenerator`). |
| **Identification** | **By domain** — `InitializeTenancyByDomain` middleware resolves the tenant from the request host. |

## Event Pipeline (`TenancyServiceProvider`)

On `TenantCreated`: `CreateDatabase` → `MigrateDatabase` (both synchronous, not queued). Tenant seed (`SeedTenantDatabase`) is called **manually** after the pipeline in `CreateTenant.php`, not as an event listener.

`SeedTenantDatabase` runs inside `$tenant->run(...)` to:
1. Create/update a `TenantUser` (guard `tenant`) with email = tenant email (or fallback)
2. Call `StoreTenantPermissionsArray()` to sync Spatie permissions
3. Call `setupStoreAdminRole()` to create the `Store Admin` role and assign all permissions

## Tenancy Bootstrappers

When tenancy is initialized (`tenancy()->init()`), these bootstrappers scope Laravel to the tenant:
- **Database** — switches to the tenant's database connection
- **Cache** — tags all cache entries with the tenant ID
- **Filesystem** — suffixes storage paths with the tenant ID
- **Queue** — prefixes queue names with the tenant ID

They are reverted when tenancy ends.

## Two Filament Panels

| Panel | Guard | Routes | Resources |
|---|---|---|---|
| **Admin** (`/admin`) | `admin` (central) | Central web routes | Discovers `app/Filament/Resources/` |
| **Tenant** (`/app`) | `tenant` (per-tenant) | `routes/tenant.php` | Discovers `app/Filament/Tenant/{Resources,Pages,Widgets}/` |

The tenant panel uses `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` + `EnsureTenantIsInitialized` middleware.

## Creating a Tenant

1. `Tenant::create($data)` — saves row in central `tenants` table, triggers `TenantCreated` event
2. Event pipeline creates tenant DB and runs tenant migrations
3. `$tenant->createDomain($domain)` — adds row in central `domains` table
4. `$tenant->subscriptions()->create($planData)` — adds row in central `tenant_subscriptions` table
5. `SeedTenantDatabase` — creates admin user + permissions + role inside the tenant DB

## API Endpoint

`POST /api/tenants` — `StoreTenantRequest` validates input, then follows the same flow as the Filament `CreateTenant` page.

## Test Considerations

- Tests use file-based SQLite (`database/testing.sqlite`) with `RefreshDatabase`
- Tenant DB creation runs synchronously during tests (creates SQLite files)
- Each tenant test creates an actual tenant DB + runs migrations + seeds it
