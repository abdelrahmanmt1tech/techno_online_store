# Tenant modules (per-module subscription)

**Status:** wired to `packages` gating (initial billing wiring)  
**Decision date:** 2026-08-02 / 2026-08-03

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
| Helpers | `app/Helper/TenantModuleHelper.php` → `tenant_module_enabled()`, `tenant_accounting_active()` |

```php
tenant_module_enabled('crm');                 // or TenantModule::Crm
tenant_module_enabled(TenantModule::Pos);
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

## Automatic journal posting

Automatic document → GL posting (sales/purchase invoices, payments, POS) must run **only if accounting is active**:

```php
if (! tenant_accounting_active()) {
    return; // skip Post*ToJournalService
}
```

- Accounting UI (manual journals, COA, trial balance) can still be hidden later via `tenant_module_enabled('accounting')` on nav/`canAccess`.
- Auto-post is a **capability of the Accounting module**, not of Store/POS alone.
- Store/POS create commerce documents regardless; they simply do not create `operations`/`entries` when Accounting is off.

## Suggested call sites (later waves)

| Area | Check |
|---|---|
| CRM panel / CRM resources nav | `tenant_module_enabled('crm')` |
| POS routes / PosRegisters | `tenant_module_enabled('pos')` |
| Storefront admin resources (optional) | `tenant_module_enabled('store')` |
| Accounting nav + period close | `tenant_module_enabled('accounting')` |
| `PostSalesInvoiceToJournalService` etc. | `tenant_accounting_active()` at the top |

Do **not** mass-wire these yet unless a wave explicitly asks — the package wiring is the reference hook and existing callers already use the helpers.

## Wave 2 implication

Wave 2 auto-post services must call `tenant_accounting_active()` before writing journals. CRM↔commerce bridge features should check both source modules when relevant (e.g. CRM + Store).

## Data model

See `docs/subscriptions-packages-plan.md` for the `packages` / `prices` / `tenant_packages` tables, the Filament `Packages` resource, and the `/api/home` `packages` response.
