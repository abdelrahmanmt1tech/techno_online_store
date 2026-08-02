# Wave 2 progress log

**Date:** 2026-08-02

## Done

### Modules gate (pre-req)
- `TenantModule` / `TenantModuleGate` / `tenant_module_enabled()` / `tenant_accounting_active()` — always `true` until billing
- Docs: `docs/tenant-modules.md`

### 2.0 CRM stabilize
- CRM seeders wired into `TenantDataSeeder` (+ Walk-in/POS/Marketplace/Phone sources)
- LeadClients page
- Charts rewritten to Filament `ChartWidget::getData()`
- Print controllers + tenant routes `crm.reports.*.print`
- `User` → `TenantUser` in CRM Filament/Services
- `commission_percentage` on TenantUser form

### 2.1–2.4 Accounting posting
- Posting settings on `AccountingSettings` + `AccountTreesSeeder` defaults
- `assertEntriesBalanced` re-enabled
- Services under `app/Services/Accounting/Posting/*`
- Wired into CreateSalesInvoice / RecordInvoicePayment / CreatePurchaseInvoice / PostSalesReturn
- Gate: `tenant_accounting_active()` + `postingConfigured()` (skip if settings incomplete — does not break invoices)
- Purchase path: Wave **2a** simplified (Dr Inventory / Cr AP at invoice; GR remains FIFO-only)
- Tests: `AccountingJournalPostingTest` (2 passed)

### 2.5 CRM commerce bridge (minimal)
- Migration `100800` — `opportunities.sale_id` / `sales_invoice_id`
- Opportunity form link fields

## Remaining / deferred
- Follow-up due badges / employee CRM widgets
- Auto create-sale-from-won opportunity
- Commission payout → journal
- Excel exporters
- Spatie CRM/accounting permission keys
- Existing tenants: run AccountTrees posting settings manually or re-seed settings keys if COA already exists (seeder no-ops when tree non-empty)

## Existing tenant note
If `account_trees` already seeded, new posting TenantSetting keys are **not** auto-filled (empty-only seeder). Set them in Accounting Settings UI or a one-off artisan command later.
