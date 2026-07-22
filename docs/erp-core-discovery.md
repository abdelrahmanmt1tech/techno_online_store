# ERP Core — Discovery

**Date:** 2026-07-22  
**Branch:** `feature/erp-core-fifo` (from `dev` @ `d76363a`)  
**Stash before work:** `pre-erp-core-safety-20260722-140620` (untracked `PROMBET.text`)

## Summary of current architecture

Multi-tenant Laravel 13 + Filament 5 + stancl/tenancy. Central DB holds tenants/admins; each tenant has its own MySQL database (`rwadsolu_tenant_{uuid}` per live `config/tenancy.php`). Store commerce (products, variants, orders, customers) lives entirely in the tenant DB with `$connection = 'tenant'`.

Store stock today is **simple integer counters** on `products.quantity` / `product_variants.quantity` with `track_stock` / `disable_orders_for_no_stock`. There is **no** warehouse, branch, FIFO, purchase, or ERP sales module.

## Files inspected

| Area | Paths |
|---|---|
| Tenancy | `AGENTS.md`, `composer.json`, `config/tenancy.php`, `app/Providers/TenancyServiceProvider.php`, `app/Models/Tenant.php` |
| Commerce models | `app/Models/Tenant/{Product,ProductVariant,Order,OrderItem,Customer,CustomerContact}.php` |
| Migrations | `database/migrations/tenant/2026_07_09_000002_create_products_tables.php`, `2026_07_14_000001_replace_product_attributes_with_variations.php`, order/customer/cart/coupon migrations |
| Auth | `app/Models/TenantUser.php` |
| Filament pattern | `app/Filament/Tenant/Resources/Products/*`, `Orders/*` |
| Translations | `lang/{en,ar}/dashboard.php` |
| Tests | `tests/Feature/WhatsApp/WhatsAppTestCase.php`, `phpunit.xml` |
| Helpers | `app/Helper/{PermissionsArray,TenantPermissionsArray,SeoHelper}.php` |

## Reuse

- Filament Tenant Resource layout: `Resource` + `Schemas/` + `Tables/` + `Pages/`.
- Filament v5: `Filament\Schemas\Components\Section`, `form(Schema $schema)`.
- Labels via `__()`; new keys in `lang/{en,ar}/erp.php`.
- Money display: Eloquent `decimal:2` casts; DB `decimal(12,2)` for money (align variants’ historical `10,2` by using `12,2` for ERP).
- Tenant test bootstrap pattern from WhatsAppTestCase (`RefreshDatabase` + empty `$connectionsToTransact` + remigrate).
- Existing `Product`, `ProductVariant`, `Order`, `Customer`, `TenantUser` as FK targets only.
- BCMath is available — use a small `Decimal` helper (no new money package).

## Create (greenfield)

- Tenant migrations for branches, warehouses, UoM, inventory items, commerce links, stock ledger/FIFO, purchases, sales, invoices, payments, document sequences, commerce quantity adjustments.
- Models under `App\Models\Tenant\` (ERP domain).
- Enums under `App\Enums\Erp\`.
- Services/Actions under `App\Services\Erp\` and `App\Actions\Erp\`.
- Filament Tenant Resources under dedicated navigation groups.
- Feature/Unit tests under `tests/Feature/Erp/` and `tests/Unit/Erp/`.
- Docs listed in the prompt (`architecture`, `implementation-log`, `test-plan`, `manual-testing`, `handover`).

## Risks noted

1. **Two stock systems** — easy to accidentally sync via observers; must keep impacts explicit in Actions only.
2. **Integer commerce qty vs decimal ERP qty** — must reject fractional commerce deltas.
3. **No existing `created_by`/`updated_by`** — introduce for ERP tables only.
4. **No activity log package** — use `commerce_quantity_adjustments` + document fields for audit of commerce impacts.
5. **Float math in store checkout** — ERP must not copy that; use BCMath.
6. **Tenant migrations only** — never touch central plans/subscriptions.
7. **Filament form totals** — UI live calc is display-only; server Actions recompute.
8. **Docs drift** — AGENTS says `technomasrsystem_tenant`; config uses `rwadsolu_tenant_` (document, do not “fix” prefix in this task).

## Architectural decisions (final)

| Decision | Choice |
|---|---|
| Separation | Commerce qty and ERP stock never auto-sync; only explicit receipt/return Actions may adjust both. |
| Commerce link | Table `inventory_item_commerce_links` with unique `(source_type, source_id)` and unique `inventory_item_id` (one store source per item). |
| Costing | FIFO only (`costing_method = fifo`); layers + consumptions. |
| Decimal math | BCMath via `App\Support\Erp\Decimal`. |
| Money precision | `decimal(14,4)` for unit costs/qty in ERP; `decimal(14,2)` for invoice money totals. |
| Qty precision | `decimal(14,4)` for ERP quantities. |
| Document numbers | `document_sequences` + `DocumentNumberService` with `lockForUpdate()`. |
| Sale ↔ Order | `sales.order_id` nullable FK + unique partial uniqueness enforced in app for non-cancelled/reversed sales (SQLite/MySQL compatible: unique index on `order_id` where status not cancelled — use app rule + index on `order_id`; document: prevent second *active* sale per order in Action). |
| Invoice ↔ Order | `sales_invoices.order_id` nullable, indexed, **not** unique; copied from sale; cannot diverge. |
| Soft deletes | Master data only (branches, warehouses, units, items, suppliers). Posted documents: reverse, never delete. |
| Permissions | Deferred (workspace rule); no new Spatie keys / `canAccess`. |
| Observers | **None** for stock/commerce qty. |
| Default branch/warehouse | Not auto-created. |
| branch_user | Pivot for future scope; Tenant Admin sees all. |
| Stock documents | Unified `stock_transactions` + type-filtered Filament Resources. |
| Payments | Polymorphic-style `invoice_payments` with `payable_type` + `payable_id` (purchase_invoice \| sales_invoice). |
| Namespace | Models: `App\Models\Tenant\`; Services: `App\Services\Erp\`; Actions: `App\Actions\Erp\`; Enums: `App\Enums\Erp\`. |

## Implementation order

A Discovery → B shared core → C stock/FIFO → D purchases → E sales/invoices/payments → F Filament → G docs/tests/pint/build.
