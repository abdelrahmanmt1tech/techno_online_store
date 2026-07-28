<?php

/**
 * Commerce Core — Shared Catalog, Unified Sales Engine, POS Foundation
 *
 * Branch: feature/erp-next-phase
 * Date: 2026-07-28
 */

## Goal

Establish a **Business Core** shared by Online Store, ERP, POS, and future API — without duplicating product models or sales business rules.

## Architecture

```text
Storefront / Filament / future POS Blade+Vue
            │
            ▼
   Thin Controllers / Filament Actions
            │
            ▼
 ┌──────────────────────────────┐
 │  UnifiedSalesEngine          │  App\Services\Commerce
 │  CatalogService              │
 │  CashierSessionService       │  App\Services\Pos
 └──────────────┬───────────────┘
                │
                ▼
 Existing ERP Actions (unchanged contracts)
 ConfirmSaleAction / CreateSalesInvoiceAction /
 RecordInvoicePaymentAction / PostSalesReturnAction /
 FifoCostingService / CommerceQuantityService
```

**Rules**

- Business logic lives in Services / Actions — not Controllers or Vue.
- **One** product catalog (`products` + variants + categories + brands + attributes).
- **One** sales engine for confirm / invoice / payment / return / suspend.
- Store checkout (`CheckoutController` / OTP) still creates `Order` and decrements store qty directly — **behavior unchanged** in this phase. Future store fulfillment may call the engine explicitly without double-deducting stock.
- No Vue POS SPA, no Sanctum, no Vue Router in this phase.

## Phase 8.1 — Shared Commerce Catalog

### Reused (not recreated)

- `products`, `product_variants`, `product_variations*`, `categories`, `media`, `seos`
- ERP `units_of_measure`, `inventory_items`, `inventory_item_commerce_links`

### Added / extended

| Area | Change |
|---|---|
| `brands` | New table + model + Filament resource |
| `attributes` / `attribute_values` / `attribute_product` | Global attribute catalog (alongside per-product variations) |
| `products` | Extended: `brand_id`, `unit_id`, `catalog_type`, `status`, `visibility`, `barcode`, `allow_backorders`, `low_stock_alert`, `tax_class`, weight/dimensions, `notes`, `meta` |
| `product_variants` | Extended: `barcode`, weight/dimensions, `meta` |
| Enums | `CatalogProductType`, `ProductStatus`, `ProductVisibility` under `App\Enums\Commerce` |

### Catalog types

`inventory_item`, `service`, `digital`, `bundle`, `raw_material`, `non_stock_item`

- Legacy storefront column `type` (`physical`|`digital`) is **kept** and synced from `catalog_type` on save.
- Inventory tracking defaults: inventory_item + raw_material track; service / digital / bundle / non_stock do not.
- **Bundle stock deduction is deferred** (design only this phase).

## Phase 8.2 — Unified Sales Engine

Class: `App\Services\Commerce\UnifiedSalesEngine`

Channel enum: `SaleChannel` = `store` | `erp` | `pos` | `api` → maps to `SaleSourceType`.

Responsibilities:

- `createDraftSale`, `confirm`, `issueInvoice`, `recordPayment`, `postReturn`
- `completeSale` (confirm + optional invoice)
- `suspend` / `resume` (draft sales only)

Delegates stock/FIFO/invoice math to existing ERP Actions. Filament Sale confirm/invoice buttons call the engine.

POS channel **requires** an open `CashierSession`.

## Phase 8.3 — POS Foundation (backend only)

Tables: `cash_drawers`, `pos_registers`, `pos_payment_methods`, `pos_settings`, `cashier_sessions`, `cash_movements`

Sales columns: `pos_register_id`, `cashier_session_id`, `is_suspended`, `suspended_at`, `resumed_at`

Services: `CashierSessionService` (open/close/expected balance), `PosReceiptNumberService`

Filament (Tenant): Brands, Cash Drawers, POS Registers, Payment Methods, Cashier Sessions (read-only), POS Settings (singleton)

Policies under `App\Policies\Tenant\` (ready for hardening; Filament `can*()` permission gates deferred per project rule).

Future POS UI path (not built yet):

```text
Laravel route → Blade → #pos-app → Vue components → Axios + CSRF/session → Controllers → Services
```

## Commerce vs ERP stock (unchanged)

Store qty and ERP FIFO remain separate. Observers must not sync them. Cross-impact only via explicit Actions + `commerce_quantity_adjustments`.

## Tests

- Existing: `php artisan test --filter=Erp` (must stay green)
- New: `tests/Feature/Commerce/CommerceCoreTest.php`

## Deferred

- Vue / Blade POS presentation layer
- Bundle BOM + stock explosion
- Routing store checkout through UnifiedSalesEngine
- Automatic Order → Sale conversion
- Spatie permission keys for POS / commerce
- Weighted average costing / GL
