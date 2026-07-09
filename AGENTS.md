# Techno Online Store

## Stack

- **Laravel 13** / PHP ^8.3 / Filament ~5.0 / Tailwind CSS v4 / Vite
- **stancl/tenancy** — multi-tenant app (central DB `techno_online_store` + per-tenant databases)
- **MySQL** — DB, sessions (`database`), cache (`database`), queues (`database`)
- **Locale**: Arabic (`ar`) in `.env`. `.env.example` defaults to SQLite; actual `.env` uses MySQL.

## Commands

| Command | Action |
|---|---|
| `composer run setup` | Fresh install: install deps, create `.env`, key:generate, migrate, `npm install && npm run build` |
| `composer run dev` | Start dev server + queue + logs + Vite (4 concurrent processes) |
| `composer run test` | `config:clear` then `php artisan test` |
| `npm run build` / `npm run dev` | Vite build / dev |
| `./vendor/bin/pint` | Lint (Laravel Pint) |
| `php artisan db:seed --class=AdminSeeder` | Seed super admin (`admin@gmail.com` / `password`) — also syncs permissions |

## Architecture

- **Two Filament panels** (separate auth guards, primary color `Amber`):
  - **Admin panel** (`/admin`, panel ID `admin`, `authGuard('admin')`) — central management. Discovers `app/Filament/Resources/`.
  - **Tenant panel** (`/app`, panel ID `tenant`, `authGuard('tenant')`) — per-tenant. Discovers `app/Filament/Tenant/{Resources,Pages,Widgets}/`. Uses `InitializeTenancyByDomain` + `PreventAccessFromCentralDomains` + `EnsureTenantIsInitialized` middleware; routes in `routes/tenant.php`.
- **Central DB** tables: `admins`, `tenants`, `domains`, `permissions`, `roles`, `role_has_permissions`, `model_has_permissions`, sessions/cache/jobs.
- **Per-tenant DBs** created synchronously. The tenancy event pipeline (`TenancyServiceProvider`) runs `CreateDatabase` → `MigrateDatabase`. `SeedTenantDatabase` is called separately in `CreateTenant.php:30-33` after the pipeline completes. Tenant migrations: `database/migrations/tenant/`.
- **Tenant creation flow** (in `CreateTenant.php`): save Tenant → create domain → tenancy event pipeline fires (CreateDatabase → MigrateDatabase) → `SeedTenantDatabase::handle()` called synchronously via `app()`.
- **Tenant admin password**: `CreateTenant` passes password to `SeedTenantDatabase`. The job uses `config('auth.providers.tenant_users.model')` to create the first user. Password hashing handled by model's `hashed` cast.
- **Auth models**: `App\Models\Admin` (`$guard_name = 'admin'`, `table: admins`, central DB) and `App\Models\TenantUser` (`$guard_name = 'tenant'`, `table: users`, per-tenant DB). Both use spatie `HasRoles`.
- **Auth providers**: `admins` → `Admin::class`, `tenant_users` → `TenantUser::class`. Admin panel uses `admin` guard → `admins` provider → central DB. Tenant panel uses `tenant` guard → `tenant_users` provider → tenant DB (after tenancy initialization).
- **Models**: `Tenant` (stancl base, soft deletes, custom columns: name, email, phone, is_active). UUID-based id.
- **No API routes** registered. `bootstrap/app.php` has placeholder JSON handling for `api/*` paths.
- **Central domains** (config/tenancy.php): `techno_online_store.localhost`, `online-store.technomasrsystems.com`.

## Filament Resources

- Custom admin resources under `app/Filament/Resources/{Admins,Roles,Tenants}/`. Each has `Pages/`, `Schemas/`, `Tables/` subdirectories.
- Tenant resources under `app/Filament/Tenant/Resources/Categories/`.
- Navigation labels use `__('dashboard.*')` translations (`lang/{ar,en}/dashboard.php`).
- Run `php artisan make:filament-resource` for new resources.

## Permissions

- **spatie/laravel-permission** with `admin` guard. Custom migration adds `display_name` and `group_name` columns to the permissions table.
- `app/Helper/PermissionsArray.php` (auto-loaded via `composer.json` `files`) defines:
  - `permissionsArray()` — all permissions grouped: roles, tenants, admins.
  - `StorePermissionsArray()` — syncs permissions to DB (creates/updates/deletes).
- Admin `id == 1` bypasses all checks (`Gate::before` in `AppServiceProvider`).
- Permission keys follow pattern: `{group}.{action}` (e.g. `tenants.view`, `admins.create`).
- Resources use static `can*()` methods per permission key.

## Testing

- **PHPUnit** (not Pest) — `tests/Unit/` and `tests/Feature/`.
- In-memory SQLite (`:memory:`) in `phpunit.xml`. `QUEUE_CONNECTION=sync`, `CACHE_STORE=array`, `SESSION_DRIVER=array`.
- `tests/TestCase.php` extends `Illuminate\Foundation\Testing\TestCase` (no `RefreshDatabase` by default — add trait when tests need DB).
- Unit tests extend `PHPUnit\Framework\TestCase` directly (no Laravel app boot).

## Code Style

- Laravel Pint for formatting, 4-space indentation per `.editorconfig`.
- No dedicated npm lint script.
