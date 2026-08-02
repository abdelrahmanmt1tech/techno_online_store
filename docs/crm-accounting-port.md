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
- [x] Morph map client/customer; erp.nav.accounts

## Test results — 2026-08-02

### Passed
- App boots (`php artisan about`)
- Panels registered: `tenant`, `crm`, `admin`
- CRM panel: path `app/crm`, 11 resources, 9 pages
- Tenant panel discovers CRM + accounting resources
- Class reflection load: 33/33 OK
- Tenant migrations applied on `db1cbf24-94d3-4a41-91e2-556c921cdd50`
- CommissionPaymentCycle form builds after adding `App\Enums\PaymentMethod`
- Fixed MySQL FK name length on commission adjustments
- Fixed `financial_periods` columns to match model (`start_date`/`end_date`)
- Added `opportunity_stages.is_final`

### Fixed during testing
- UTF-8 BOM in `ClientOpportunitiesRelationManager`
- Removed IATA from Create/Edit Client
- Simplified `OperationsTable` (stripped Ticket/Payment/Franchise)
- Stubbed ApexCharts for Laravel 13 incompatibility
- Neutralized TaxType / AccountStatement paths in Operation pages

### Remaining decisions (see chat report)
- ApexCharts package vs native ChartWidget rewrite
- Accounting exporters missing
- Hide invoice-tax UI on OperationForm
- Wire seeders + AccountTreesSeeder
- Staff filter `is_admin` (tenant has users with is_admin=0 managing panels)



## KEEP / STRIP reminders

**STRIP always:** Meta messaging pages, AccountStatement, Ticket/Reservation, flyaram Payment + SafesBank + payment reports, Franchise, ZATCA on journals.
