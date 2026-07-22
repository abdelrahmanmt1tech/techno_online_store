# ERP Core — Implementation Log

Updated during `feature/erp-core-fifo`.

## Documentation

| Path | Change | Why | Role |
|---|---|---|---|
| `docs/erp-core-discovery.md` | added | Required discovery | Baseline |
| `docs/erp-core-architecture.md` | added | Architecture + mermaid | Design |
| `docs/erp-core-test-plan.md` | added | Test inventory | QA |
| `docs/erp-core-manual-testing.md` | added | Human checklist | QA |
| `docs/erp-core-handover.md` | added | Delivery | Ops |
| `docs/erp-core-implementation-log.md` | added/updated | File ledger | Compliance |
| `AGENTS.md` | updated | ERP summary + gotchas | Agent guide |
| `.gitignore` | updated | Ignore `rwadsolu_tenant*` SQLite leftovers | Hygiene |
| `lang/en/erp.php`, `lang/ar/erp.php` | added | UI/validation i18n | UX |

## Core backend

| Path | Change | Why | Role |
|---|---|---|---|
| `database/migrations/tenant/2026_07_22_100001_*` … `100004_*` | added | ERP schema | DB |
| `app/Enums/Erp/*` | added | Typed statuses/types | Domain |
| `app/Support/Erp/Decimal.php` | added | BCMath money/qty | Math |
| `app/Services/Erp/*` | added | Numbers, FIFO, commerce, item resolve | Services |
| `app/Actions/Erp/*` | added | Posting/confirm/invoice/payment/returns | Application |
| `app/Models/Tenant/*` (ERP models + Concerns) | added | Eloquent | Persistence |
| `app/Models/Tenant/Order.php` | updated | sales(), salesInvoices() | Relations |
| `app/Models/TenantUser.php` | updated | branches() | Relations |

## Filament

| Path | Change | Why | Role |
|---|---|---|---|
| `app/Filament/Tenant/Resources/{Branches,Warehouses,...}/*` | added | Tenant ERP UI | Filament |
| `app/Filament/Tenant/Support/Erp/*` | added | Shared enum/payment helpers | Filament |

## Tests

| Path | Change | Why | Role |
|---|---|---|---|
| `tests/Feature/Erp/ErpTestCase.php` | added | Tenant bootstrap | Test infra |
| `tests/Unit/Erp/DecimalTest.php` | added | Decimal unit | Unit |
| `tests/Feature/Erp/FifoCostingTest.php` | added | FIFO cases | Feature |
| `tests/Feature/Erp/CommerceAndSaleTest.php` | added | Commerce + mixed sale | Feature |
| `tests/Feature/Erp/PurchaseAndInvoiceTest.php` | added | PO/GR/invoice/pay | Feature |
| `tests/Feature/Erp/FilamentErpSmokeTest.php` | added | Panel smoke | Filament |

## Notes

- No plan/subscription changes.
- No Meta Integration tables (ERP N/A for reset registry).
- Business logic kept out of Filament; Actions only.
- Stash: `pre-erp-core-safety-20260722-140620`
