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

**Follow-up**
- [ ] `php artisan tenants:migrate` (or sync --migrate) on a tenant
- [ ] AccountTreesSeeder sanitized + tenant seed hook
- [ ] Fix remaining Filament accounting form Ticket/Invoice UI remnants
- [ ] CRM print routes; Pint full pass; smoke `/app/crm`


## KEEP / STRIP reminders

**STRIP always:** Meta messaging pages, AccountStatement, Ticket/Reservation, flyaram Payment + SafesBank + payment reports, Franchise, ZATCA on journals.
