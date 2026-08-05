# Tenant modules (per-module subscription)

**Status:** package entitlement + UI/route enforcement wired  
**Decision date:** 2026-08-02 / 2026-08-03 / 2026-08-05

## Product decision

- **Cancelled:** selling via a single plan / package entitlement matrix as the commercial model.
- **Chosen:** the merchant subscribes to **modules**, each with its **own subscription price**.
- Current sellable modules:
  - `store` — المتجر الإلكتروني (طلبات، منتجات، فئات، كوبونات، موقع…)
  - `pos` — نقاط البيع + المخزون + المشتريات + مبيعات ERP
  - `crm` — إدارة العملاء
  - `accounting` — المحاسبة
  - `hr` — الموارد البشرية

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
- A partial package (`module = store|pos|crm|accounting|hr`) grants **its single** module.
- A package counts as active when `status` is `trial`/`active` **and** `expires_at` is in the future (trial counts because `expires_at` is computed after the trial).
- No active package → **no modules** (strict gating).
- `config('app.bypass_permissions')` (true outside production) opens every module for development.

The lookup lives in `TenantModuleGate::resolve()` / `enabledModulesForCurrentTenant()` — do not invent parallel checks in Filament `canAccess()`, Actions, or nav.

## Module ownership map

| Area | Module gate |
|---|---|
| Products + categories + brands (shared catalog) | `store` **or** `pos` |
| Branches | `store` **or** `pos` |
| Storefront admin (orders, customers, coupons, reviews, CMS, themes, governorates, contacts) + public store API | `store` |
| POS terminal `/app/pos*` + POS admin (registers, sessions, drawers…) | `pos` |
| ERP inventory (items, warehouses, receipts/issues/transfers/adjustments/damage, movements, balances) | `pos` |
| ERP sales (sales, sales invoices/returns, invoice payments) + invoice print settings + UoM | `pos` |
| Purchases (POs, GRNs, purchase invoices/returns) | `pos` |
| Suppliers | `pos` **or** `crm` |
| CRM panel `/app/crm`, CRM resources/pages, messaging inbox/connect, CRM reports print | `crm` |
| Accounting UI (journals, COA, reports, settings) | `accounting` |
| Auto journal posting (`Post*ToJournal*`) | `tenant_accounting_active()` |
| HR (employees, attendance, payroll, HR dashboard widgets, `/app/hr/*`) | `hr` |
| Users / roles / general settings / my subscriptions | no sellable-module gate |

### Store vs POS (product rule)

- **Store** is a narrow ecommerce module: storefront orders, catalog for the website, coupons, CMS.
- **POS** owns operational ERP: inventory documents, purchases, ERP sales/invoices, warehouses, and the POS terminal.
- Catalog (`products` / `categories` / `brands`) stays shared so a POS-only tenant can still sell SKUs without a storefront package.

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
| POS routes + ERP print + Pos* resources | `EnsureTenantModuleActive:pos` + `RequiresTenantModule` / `tenant_module_enabled(Pos)` |
| Storefront admin + `/api/tenant` commerce | `store` on pages/resources + middleware on store API group |
| Public module status API | `GET /api/tenant/modules` + `GET /api/tenant/modules/store` (always reachable) — see [`docs/tenant-modules-api.md`](tenant-modules-api.md) |
| Shared catalog Products/Categories/Brands | `tenant_module_any_enabled(Store, Pos)` |
| ERP inventory / purchases / ERP sales | `tenant_module_enabled(Pos)` |
| Accounting nav + pages/resources | `tenant_module_enabled('accounting')` |
| HR resources/pages + `/app/hr/*` routes | `tenant_module_enabled('hr')` + `EnsureTenantModuleActive:hr` |
| Branches | `tenant_module_any_enabled(Store, Pos)` |
| Tenant sidebar groups | filtered in `TenantNavigationBuilder` |
| `PostSalesInvoiceToJournalService` etc. | `tenant_accounting_active()` at the top |

## Dev / QA note

To verify gating locally set `BYPASS_PERMISSIONS=false`, then open a tenant with only the Store package: ecommerce items remain, while inventory/purchases/ERP sales/POS terminal disappear. With POS only: operational ERP + catalog remain; storefront orders/CMS disappear.

## Data model

See `docs/subscriptions-packages-plan.md` for the `packages` / `prices` / `tenant_packages` tables, the Filament `Packages` resource, and the `/api/home` `packages` response.
