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

## Progress

### 2026-08-02 — Initial bulk port

**Done**
- [x] Live doc + AGENTS note
- [x] CRM enums/services/support/config + Filament CRM (~99) + Clients/LeadSources
- [x] CrmPanelProvider + dual discovery; TenantUser canAccessPanel tenant+crm
- [x] Migrations CRM + Accounting foundations
- [x] Client alias; Supplier/Customer CRM+accounting columns
- [x] Strip AccountStatement sync from Operation model + Create/Edit Operation pages
- [x] Morph map soft aliases `client`/`customer` (not enforceMorphMap — breaks TenantUser and existing FQCNs)

## Test results — 2026-08-02

### Passed
- App boots; panels `tenant` + `crm` + `admin`
- CRM panel: `/app/crm` (11 resources, 9 pages)
- Class load 33/33; CommissionPaymentCycle form OK
- Migrations applied on tenant `db1cbf24-...`
- CRUD smoke: LeadSource, OpportunityStage, Client, Opportunity, AccountTree, FinancialPeriod, Operation, Entry

### Fixed during testing
- BOM, IATA client pages, OperationsTable strip, ApexCharts stub (L13)
- FK name length, `is_final`, financial_periods columns, entries.linkable
- Entry: removed SafesBank + AccountTax hooks; TenantSetting path
- PaymentMethod enum for commissions

### Open decisions for product owner
1. ApexCharts: keep stub / rewrite ChartWidget / wait for L13-compatible package
2. Accounting exporters (missing classes) — port or remove export buttons
3. Remove invoice-tax UI from OperationForm entirely
4. AccountTreesSeeder + CRM seeders into tenant pipeline
5. Staff filter: tenant user #1 has `is_admin=0` but accesses panels — tighten?
6. Soft-delete SafesBankBalanceService / PaymentCommissionEntryDisplay files




## KEEP / STRIP reminders

**STRIP always:** Meta messaging pages, AccountStatement, Ticket/Reservation, flyaram Payment + SafesBank + payment reports, Franchise, ZATCA on journals.
