# Dashboard Lite

Branch: `feature/dashboard-lite`  
Phase: 12

## Scope

Operational overview widgets inside the Tenant Filament panel (`/app`).

**In scope:** permission-aware stats, 7-day sales chart, short lists (latest sales, low stock, attendance today).  
**Out of scope:** advanced analytics, Excel/CSV export, forecasting, profit margins, new DB tables, POS feature work, Store checkout changes.

## Permissions

| Key | Purpose |
|---|---|
| `dashboard.view` | Open Dashboard page |
| `dashboard.sales.view` | Sales + collection stats, sales chart, latest sales |
| `dashboard.pos.view` | POS transaction/collection/open-shift cards |
| `dashboard.inventory.view` | Low/out-of-stock cards + low-stock list |
| `dashboard.hr.view` | HR attendance cards + attendance today list |
| `dashboard.store.view` | Existing store widgets (`StoreKpis`, `OrdersTrend`, `OrderStatusPie`) |

No fixed roles. Assign via Roles UI + `tenants:sync-permissions`.

Widget `canView()` gates both UI and query execution.

## Metric definitions

### Sales (ERP + POS)

**Source of truth:** `sales` only.

Countable statuses: `confirmed`, `partially_invoiced`, `invoiced`, `partially_returned`.  
Excluded: `draft`, `cancelled`, `reversed`, `returned`, `is_suspended = true`.

- **Sales today:** `SUM(grand_total)` / `COUNT(*)` where `sale_date = today`
- **Sales month:** same filters for current calendar month
- **Chart (7 days):** daily `SUM(grand_total)` including zero days

### Double counting

POS/ERP checkout creates **Sale + SalesInvoice** via `UnifiedSalesEngine`.  
Dashboard never sums Sale totals **and** invoice totals together.

Store `orders` remain separate (existing store widgets). Store checkout still does not create Sale rows.

### Collection

- **Unpaid due:** `SUM(due_amount)` on `sales_invoices` with status `issued` or `overdue`
- **Partially paid due:** `SUM(due_amount)` where status `partially_paid`
- Uses remaining **due**, not invoice grand total

### POS

- Transactions today: countable sales with `source_type = pos` and `sale_date = today`
- Collected today: `invoice_payments.amount` where `status = posted`, payable is sales invoice, linked sale `source_type = pos`, `paid_at` today
- Open shifts: `cashier_sessions` in `opened` or `closing`

### Inventory (ERP only)

Uses `stock_balances.quantity_on_hand` vs `inventory_items.minimum_stock`.  
Does **not** mix commerce `products.quantity`.

- Low stock: `0 < qty < minimum_stock` and `minimum_stock > 0`
- Out of stock: `qty <= 0`

### HR attendance

Mutual cards for today (`attendance_date`):

- Present = `present` only
- Late = `late` only (not counted again as present)
- Absent = `absent` only

## Widgets

| Widget | Permission |
|---|---|
| `SalesCollectionStatsWidget` | sales |
| `PosInventoryStatsWidget` | pos and/or inventory |
| `HrAttendanceStatsWidget` | hr |
| `SalesChartWidget` | sales |
| `LatestSalesWidget` | sales (links require `erp.sales.view`) |
| `LowStockWidget` | inventory |
| `AttendanceTodayWidget` | hr |
| Existing `StoreKpis` / `OrdersTrend` / `OrderStatusPie` | store |

### Excluded widgets

- **Latest store orders:** optional; store orders stay on existing store widgets to avoid touching Store APIs/checkout. Documented as deferred.

## Performance

`App\Services\Dashboard\DashboardMetricsService`:

- Aggregate SQL (`SUM`/`COUNT`/`GROUP BY`)
- Lists `limit(5)` with selective columns
- No N+1 for list relations (eager load customer/employee)
- No cache in this phase

Approximate queries when all widgets visible: ~12–15 aggregations/lists (Filament may mount widgets lazily).

## Tenant isolation

All queries use tenant connection models after tenancy is initialized by panel middleware.

## Tests

`tests/Feature/Dashboard/DashboardLiteTest.php` covers permissions, empty zeros, sales/invoice double-count guard, payments, stock, HR counts, chart zeros, list caps.
