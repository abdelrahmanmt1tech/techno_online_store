# Tenant modules (per-module subscription)

**Status:** reference stub — billing not wired yet  
**Decision date:** 2026-08-02

## Product decision

- **Cancelled:** selling via a single plan / package entitlement matrix as the commercial model.
- **Chosen:** the merchant subscribes to **modules**, each with its **own subscription price**.
- Current sellable modules:
  - `store` — المتجر
  - `pos` — نقاط البيع (POS)
  - `crm` — إدارة العملاء
  - `accounting` — المحاسبة

Central `Plan` / `TenantSubscription` UI may still exist historically; **do not** use them for feature gating going forward. All availability checks go through the shared gate below.

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

`TenantModuleGate::resolve()` **always returns `true`** for every module until real per-module subscription lookup is implemented.

**Stop / resume point:** when billing is ready, edit **only** `TenantModuleGate::resolve()` (and optionally cache). Do not invent parallel checks in Filament `canAccess()`, Actions, or nav.

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

Do **not** mass-wire these yet unless a wave explicitly asks — the stub returning `true` is enough as the reference hook.

## Wave 2 implication

Wave 2 auto-post services must call `tenant_accounting_active()` before writing journals. CRM↔commerce bridge features should check both source modules when relevant (e.g. CRM + Store).
