# Accounting + CRM QA (Wave 1)

**Date:** 2026-08-02  
**Branch target:** `dev`  
**Scope:** Plan «إكمال المحاسبة + فحص CRM» wave 1 (no invoice→journal posting).

## Passed

| Check | Result |
|---|---|
| Class load: AssistantLedger, AccountTreeStatementPage, AccountingSettings, TrialBalance, GeneralLedger, PartyAccountStatement | OK |
| Views: assistant-ledger, account-tree-statement, accounting-settings, accounting-generic-table | OK |
| Lang: trial_balance, general_ledger, party_account_statement, accounting_settings, assistant_ledger, account_tree_statement | OK |
| `Client::accTree()` / `Supplier::accTree()` | OK |
| AccountTreesSeeder (empty-only, no IATA) + TenantDataSeeder wire | OK |
| AccountTree Resource/Table URLs → Tenant Accounting pages | OK |
| `dd()` → `ValidationException` in Accounting services; `?User` → `?TenantUser` | OK |
| Dead `App\Filament\Pages\*` refs under Tenant resources | None found |
| `php artisan test --filter=Erp` | Exit 0 |

## Delivered in this wave

1. **Tree actions:** AssistantLedger + AccountTreeStatement (Entry-only) under `App\Filament\Tenant\Pages\Accounting\`
2. **COA:** `AccountTreesSeeder` seeds only when `account_trees` empty; auto-sets TenantSetting parent keys (clients `1201`, suppliers `210101`, centers `5100`, income summary `3901`, retained earnings `3103`)
3. **AccountingSettings** page (nav: `erp.nav.accounts`)
4. **TrialBalance** + **GeneralLedger** (Filament tables only; no Excel/Franchise)
5. **PartyAccountStatement** + Client/Supplier `accTree()` on save + form fields `account_tree_id` / `accounts_center_id`

## Remaining gaps (explicit — blocks “fully integrated accounting”)

### Accounting (critical)

- **No auto-post** from SalesInvoice / PurchaseInvoice / InvoicePayment / POS into `operations`/`entries`
- Excel export / dedicated print layouts for TB/GL/party statements (Filament table UI only)
- Spatie permission keys + `canAccess()` hardening (deferred by project rules; Gate bypass via `BYPASS_PERMISSIONS`)

### CRM

- ApexCharts package incompatible with Laravel 13 (stub remains)
- Meta messaging not ported (by design)
- Commission report depth vs flyaram: exporters exist; chart widgets stubbed — re-check visuals on store1 after deploy

## How to seed COA on an existing empty tenant

```bash
php artisan tenants:seed --class=Database\\Seeders\\AccountTreesSeeder
# or full tenant data
php artisan tenants:seed --class=Database\\Seeders\\TenantDataSeeder
```

Seeder **no-ops** if any non-deleted `account_trees` rows exist (safe for store1 if already seeded).

## Next recommended wave

Wire invoice/payment confirmation Actions → `AccountingOperationWriter` with idempotency keys, then smoke-post sample sales/purchase invoices on a staging tenant.
