# ERP Core — Test Plan

## Unit

| Test | File | Covers |
|---|---|---|
| Decimal math / integer qty | `tests/Unit/Erp/DecimalTest.php` | BCMath, reject 1.5 for commerce |

## Feature — FIFO / stock

| Case | File |
|---|---|
| 10×100 + 5×120 issue 12 → cost 1240, remain 3@120 | `FifoCostingTest` |
| Independent warehouses | same |
| Insufficient stock rollback | same |
| Transfer preserves costs, no commerce change | same |

## Feature — commerce & sales

| Case | File |
|---|---|
| Commerce receipt ↑ ERP + store; idempotent | `CommerceAndSaleTest` |
| Fractional commerce rejected | same |
| Mixed sale inventory+commerce+manual | same |
| Inventory sale does not change store qty | same |

## Feature — purchases & invoices

| Case | File |
|---|---|
| PO/invoice no stock until GR; partial pay | `PurchaseAndInvoiceTest` |
| Cannot over-receive PO | same |
| Sales invoice copies order_id; partial; no over-invoice | same |

## Feature — Filament smoke

| Case | File |
|---|---|
| Branch list/create persists | `FilamentErpSmokeTest` |
| Stock receipt / sale create pages load | same |

## Recommended additions (manual / follow-up)

- Variant commerce receipt only updates that variant
- Purchase return reverses commerce when applicable
- Sales return restock FIFO costs
- Damage disposition does not restock regular warehouse
- Reverse stock transaction twice blocked
- Payment amount ≤ due; negative rejected
