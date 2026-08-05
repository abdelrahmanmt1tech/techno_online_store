# CRM + Accounting Port Log

**Source:** `D:\projeccts\techno\flyaramfilament` (read-only)  
**Target:** `D:\technomasr\techno_online_store`  
**Started:** 2026-08-02

## Decisions

| Decision | Choice |
|---|---|
| Panels | `TenantPanelProvider` (`/app`) + `CrmPanelProvider` (`/app/crm`) — peer merchant panels |
| Auth | Both: `authGuard('tenant')` + domain tenancy |
| Staff FKs (flyaram `User`) | `TenantUser` where `is_admin=true` |
| Client | `App\Models\Tenant\Client extends Customer` on `customers` |
| Supplier | Extended ERP Supplier (`gondc_name`, `account_tree_id`, `accounts_center_id`); registered on CRM panel |
| Accounting Payment / SafesBank | **STRIPPED** |
| AccountStatement / Tickets / Meta | **STRIPPED** |
| Permissions | Deferred (`BYPASS_PERMISSIONS`) |
| Commercial model | **Per-module subscriptions** (store / pos / crm / accounting / hr) — gate via `TenantModuleGate` — [`docs/tenant-modules.md`](tenant-modules.md) |
| Auto GL posting | Only when `tenant_accounting_active()` (Wave 2+) |

## Progress

### 2026-08-02 — Accounting completion wave 1

- [x] AssistantLedger + AccountTreeStatement (Entry-only) + tree action URLs
- [x] AccountTreesSeeder (empty-only, no IATA) + AccountingSettings + TenantSetting keys
- [x] TrialBalance + GeneralLedger under `erp.nav.accounts`
- [x] PartyAccountStatement + Client/Supplier `accTree()` + Filament account fields
- [x] Cleanup `dd()` / wrong `User` typehints in Accounting services
- [x] QA doc: [`docs/accounting-crm-qa.md`](accounting-crm-qa.md)

**Still deferred:** invoice/POS → journal posting; Excel/print; Spatie permission keys.

### 2026-08-02 — Production smoke fixes (store1)

**Bugs fixed**
- [x] `Client::onlyTrashed()` — SoftDeletes on `Customer` (+ `deleted_at` from CRM migration)
- [x] `parent_follow_up_id` — align migration `100500` (rename `parent_id` if present) + content columns
- [x] `accounts_center_movements.movement_date` / `notes` — migration `100600`
- [x] Strip Franchise + Branch `account_tree_id` from AccountTreeCleanupPage
- [x] AccountsCenterDetailsReport: no Franchise/ticket/exporter/widget; safe without movement_date
- [x] Hide missing Excel exporters (operations, opening entries, period balances, centers, BS/P&L)
- [x] Accounting Blade views + Tenant `FinancialPeriod` namespace in BS/P&L filters
- [x] Merge missing `dashboard.*` keys from flyaram into `lang/{ar,en}/dashboard.php` (~285 keys)

**Views audit (2026-08-02):** financial-period opening-entry create/edit blades + `filament.notes-modal` copied; CRM report/print/timeline views present.

**Cleanup:** deleted leftover flyaram stubs `SafesBankBalanceService` + `PaymentCommissionEntryDisplay` (never wired; Payment/SafesBank not ported).

### 2026-08-02 — Initial bulk port

**Done**
- [x] Live doc + AGENTS note
- [x] CRM enums/services/support/config + Filament CRM (~99) + Clients/LeadSources
- [x] CrmPanelProvider + dual discovery; TenantUser canAccessPanel tenant+crm
- [x] Migrations CRM + Accounting foundations
- [x] Client alias; Supplier/Customer CRM+accounting columns
- [x] Strip AccountStatement sync from Operation model + Create/Edit Operation pages
- [x] Morph map soft aliases `client`/`customer` (not enforceMorphMap — breaks TenantUser and existing FQCNs)

## Open decisions

1. ApexCharts stub vs ChartWidget rewrite
2. Port accounting Excel exporters later
3. Wire Sales/Purchase/POS → journal posting (next accounting wave)
4. Staff `is_admin` gate tightening

## KEEP / STRIP reminders

**STRIP always:** Meta messaging pages, AccountStatement, Ticket/Reservation, flyaram Payment + SafesBank + payment reports, Franchise, ZATCA on journals.
