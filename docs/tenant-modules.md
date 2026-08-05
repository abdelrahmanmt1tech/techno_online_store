# Tenant modules (per-module subscription)

**Status:** package entitlement + UI/route enforcement wired  
**Decision date:** 2026-08-02 / 2026-08-03 / 2026-08-05

## Product decision

- **Cancelled:** selling via a single plan / package entitlement matrix as the commercial model.
- **Chosen:** the merchant subscribes to **modules**, each with its **own subscription price**.
- Current sellable modules:
  - `store` — المتجر
  - `pos` — نقاط البيع (POS)
  - `crm` — إدارة العملاء
  - `accounting` — المحاسبة

Central `Plan` / `TenantSubscription` UI was removed with the `packages` migration; **do not** reintroduce them for feature gating. All availability checks go through the shared gate below.

## Shared gate (the only place to change later)

| Piece | Path |
|---|---|
| Enum | `app/Support/Modules/TenantModule.php` |
| Gate | `app/Support/Modules/TenantModuleGate.php` |
| Helpers | `app/Helper/TenantModuleHelper.php` → `tenant_module_enabled()`, `tenant_module_any_enabled()`, `tenant_accounting_active()` |
| Middleware | `app/Http/Middleware/EnsureTenantModuleActive.php` |
| CRM Filament trait | `app/Filament/Concerns/HasTenantFeatureAccess.php` |
| Tenant Filament trait | `app/Filament/Concerns/RequiresTenantModule.php` |
| Sidebar | `app/Support/Filament/TenantNavigationBuilder.php` |

```php
tenant_module_enabled('crm');                 // or TenantModule::Crm
tenant_module_enabled(TenantModule::Pos);
tenant_module_any_enabled(TenantModule::Store, TenantModule::Pos); // shared catalog
TenantModuleGate::accountingActive();         // alias helper: tenant_accounting_active()
```

### Current behaviour

Modules are granted from the current tenant's **active `tenant_packages`** rows:

- A package with `is_full_package = true` grants **all** modules.
- A partial package (`module = store|pos|crm|accounting`) grants **its single** module.
- A package counts as active when `status` is `trial`/`active` **and** `expires_at` is in the future (trial counts because `expires_at` is computed after the trial).
- No active package → **no modules** (strict gating).
- `config('app.bypass_permissions')` (true outside production) opens every module for development.

The lookup lives in `TenantModuleGate::resolve()` / `enabledModulesForCurrentTenant()` — do not invent parallel checks in Filament `canAccess()`, Actions, or nav.

## Module ownership map

| Area | Module gate |
|---|---|
| Products + categories (admin catalog) | `store` **or** `pos` |
| Brands, UoM, inventory/stock, purchases, ERP sales/invoices | `store` **or** `pos` |
| Suppliers | `store` **or** `pos` **or** `crm` |
| Storefront admin (orders, coupons, CMS, themes…) + public store API | `store` |
| POS terminal `/app/pos*` + POS Filament resources | `pos` |
| CRM panel `/app/crm`, CRM resources/pages, messaging inbox/connect, CRM reports print | `crm` |
| Accounting UI (journals, COA, reports, settings) | `accounting` |
| Auto journal posting (`Post*ToJournal*`) | `tenant_accounting_active()` |
| HR + users/roles/general settings | no sellable-module gate |

## Automatic journal posting

Automatic document → GL posting (sales/purchase invoices, payments, POS) must run **only if accounting is active**:

```php
if (! tenant_accounting_active()) {
    return; // skip Post*ToJournalService
}
```

- Accounting UI is hidden/blocked via `tenant_module_enabled('accounting')` on nav/`canAccess`.
- Auto-post is a **capability of the Accounting module**, not of Store/POS alone.
- Store/POS create commerce documents regardless; they simply do not create `operations`/`entries` when Accounting is off.

## Wired call sites

| Area | Check |
|---|---|
| CRM panel / CRM resources/pages/widgets | `tenant_module_enabled('crm')` via `HasTenantFeatureAccess` + `EnsureTenantModuleActive:crm` on `/app/crm` |
| Client + LeadSource resources | extend `CrmResource` (same CRM gate) |
| POS routes / Pos* resources | `EnsureTenantModuleActive:pos` + `RequiresTenantModule` |
| Storefront admin + `/api/tenant` commerce | `store` on pages/resources + middleware on store API group |
| Public module status API | `GET /api/tenant/modules` + `GET /api/tenant/modules/store` (always reachable) — see [`docs/tenant-modules-api.md`](tenant-modules-api.md) |
| Shared catalog Products/Categories | `tenant_module_any_enabled(Store, Pos)` |
| Accounting nav + pages/resources | `tenant_module_enabled('accounting')` |
| Tenant sidebar groups | filtered in `TenantNavigationBuilder` |
| `PostSalesInvoiceToJournalService` etc. | `tenant_accounting_active()` at the top |

## Dev / QA note

To verify gating locally set `BYPASS_PERMISSIONS=false`, then open a tenant with only one active package (e.g. Store only). CRM/POS/Accounting sections and routes must disappear / 404.

## Data model

See `docs/subscriptions-packages-plan.md` for the `packages` / `prices` / `tenant_packages` tables, the Filament `Packages` resource, and the `/api/home` `packages` response.
