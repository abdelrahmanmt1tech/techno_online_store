# ERP Core — Handover

## Branch

- **Branch:** `feature/erp-core-fifo`
- **Base:** `dev` @ `d76363a` (after `git pull --ff-only`)
- **Not merged** into `dev` / `main`

## Stash before work

`pre-erp-core-safety-20260722-140620` (untracked `PROMBET.text`)

## Commits

See `git log dev..HEAD --oneline` after push. Expected logical commits: discovery → core/migrations/services → filament/i18n → docs/handover.

## Migrations (tenant only)

1. `2026_07_22_100001_create_erp_core_tables.php`
2. `2026_07_22_100002_create_erp_stock_tables.php`
3. `2026_07_22_100003_create_erp_purchase_tables.php`
4. `2026_07_22_100004_create_erp_sales_tables.php`

Apply on tenants via existing tenancy migrate / `tenants:sync-permissions --migrate` (do **not** run on production casually; never `migrate:fresh`).

## Models

Under `app/Models/Tenant/`: Branch, Warehouse, UnitOfMeasure, InventoryItem, InventoryItemCommerceLink, DocumentSequence, StockBalance, StockTransaction(+Line), StockMovement, StockCostLayer, StockLayerConsumption, CommerceQuantityAdjustment, Supplier, PurchaseOrder(+Item), GoodsReceipt(+Item), PurchaseInvoice(+Item), PurchaseReturn(+Item), Sale(+Item), SalesInvoice(+Item), SalesReturn(+Item), InvoicePayment.

Updated: `Order` (+sales/salesInvoices), `TenantUser` (+branches).

## Services / Actions

- `App\Services\Erp\*`: DocumentNumberService, FifoCostingService, CommerceQuantityService, InventoryItemResolver, Decimal (`App\Support\Erp\Decimal`)
- `App\Actions\Erp\*`: Post/Reverse stock, ConfirmSale, PostGoodsReceipt, ApprovePurchaseOrder, CreateSales/PurchaseInvoice, RecordInvoicePayment, PostSales/PurchaseReturn

## Filament Resources

20 tenant resources under `app/Filament/Tenant/Resources/` (settings/inventory/purchases/sales groups). Labels: `lang/{en,ar}/erp.php`.

## Tests

```bash
php artisan test --filter=Erp
```

**Result (local):** 25 passed, 0 failed (101 assertions). Includes Unit Decimal, FIFO, commerce/sales, purchases/invoices, Filament smoke.

## Pint

`./vendor/bin/pint --dirty` — success (auto-fixed style).

## Build

Requires `npm install` then `npm run build`. Document if `node_modules` was missing in the environment.

## Runbook

1. Checkout `feature/erp-core-fifo`
2. Migrate tenant DBs (tenancy path)
3. Login tenant panel `/app`
4. Follow `docs/erp-core-manual-testing.md`

## Known limits / deferred

- No plan feature gates; permissions deferred
- No POS / auto Order→Sale / continuous commerce sync
- No full GL / credit notes accounting
- Weighted average costing not implemented
- Filament coverage is smoke-level; expand action E2E as needed
- Sale multi-warehouse confirm posts per-line warehouses correctly; stock document UI uses shared StockTransaction model filters

## Decisions worth noting

- Active sale uniqueness per `order_id` enforced in Action (not DB unique) so cancelled/reversed can reuse
- `sales_invoices.order_id` indexed, not unique
- `commerce_quantity_adjustment_id` lives on GR/sale/return items, not stock lines
- BCMath via custom Decimal helper (extension available)
- gitignore extended for `database/rwadsolu_tenant*`

## Push

Record whether `git push -u origin feature/erp-core-fifo` succeeded in the final report.
