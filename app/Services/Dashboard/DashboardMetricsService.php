<?php

namespace App\Services\Dashboard;

use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\InvoiceStatus;
use App\Enums\Erp\SaleSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Hr\AttendanceStatus;
use App\Enums\Pos\CashierSessionStatus;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SalesInvoice;
use App\Models\Tenant\StockBalance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * مقاييس Dashboard Lite — مصدر واحد للحقيقة بدون Double Counting.
 *
 * المبيعات: جدول sales فقط (UnifiedSalesEngine ينشئ Sale + Invoice معًا؛ لا نجمع الاثنين).
 * التحصيل: invoice_payments الفعلية ذات status=posted على فواتير المبيعات.
 */
final class DashboardMetricsService
{
    /**
     * حالات البيع المعتمدة للإيراد التشغيلي.
     *
     * @return list<SaleStatus>
     */
    public function countableSaleStatuses(): array
    {
        return [
            SaleStatus::Confirmed,
            SaleStatus::PartiallyInvoiced,
            SaleStatus::Invoiced,
            SaleStatus::PartiallyReturned,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Sale>
     */
    public function countableSalesQuery()
    {
        return Sale::query()
            ->where('is_suspended', false)
            ->whereIn('status', $this->countableSaleStatuses());
    }

    /**
     * @return array{
     *     sales_today_total: string,
     *     sales_month_total: string,
     *     sales_today_count: int
     * }
     */
    public function salesStats(?Carbon $now = null): array
    {
        $now ??= now();
        $today = $now->toDateString();
        $monthStart = $now->copy()->startOfMonth()->toDateString();
        $monthEnd = $now->copy()->endOfMonth()->toDateString();

        $todayAgg = $this->countableSalesQuery()
            ->whereDate('sale_date', $today)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(grand_total), 0) as total')
            ->first();

        $monthTotal = (string) $this->countableSalesQuery()
            ->whereBetween('sale_date', [$monthStart, $monthEnd])
            ->sum('grand_total');

        return [
            'sales_today_total' => number_format((float) ($todayAgg->total ?? 0), 2, '.', ''),
            'sales_month_total' => number_format((float) $monthTotal, 2, '.', ''),
            'sales_today_count' => (int) ($todayAgg->cnt ?? 0),
        ];
    }

    /**
     * @return array{
     *     pos_today_count: int,
     *     pos_today_collected: string,
     *     open_shifts: int
     * }
     */
    public function posStats(?Carbon $now = null): array
    {
        $now ??= now();
        $today = $now->toDateString();

        $posTodayCount = (int) $this->countableSalesQuery()
            ->where('source_type', SaleSourceType::Pos)
            ->whereDate('sale_date', $today)
            ->count();

        // تحصيل POS = مدفوعات posted مرتبطة بفواتير مبيعات مصدرها POS
        $posCollected = (string) InvoicePayment::query()
            ->where('invoice_payments.status', 'posted')
            ->where('invoice_payments.payable_type', InvoicePayableType::SalesInvoice)
            ->whereDate('invoice_payments.paid_at', $today)
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('sales_invoices')
                    ->join('sales', 'sales.id', '=', 'sales_invoices.sale_id')
                    ->whereColumn('sales_invoices.id', 'invoice_payments.payable_id')
                    ->where('sales.source_type', SaleSourceType::Pos->value)
                    ->where('sales.is_suspended', false);
            })
            ->sum('invoice_payments.amount');

        $openShifts = (int) CashierSession::query()
            ->whereIn('status', [
                CashierSessionStatus::Opened,
                CashierSessionStatus::Closing,
            ])
            ->count();

        return [
            'pos_today_count' => $posTodayCount,
            'pos_today_collected' => number_format((float) $posCollected, 2, '.', ''),
            'open_shifts' => $openShifts,
        ];
    }

    /**
     * @return array{
     *     unpaid_due_total: string,
     *     partially_paid_due_total: string
     * }
     */
    public function collectionStats(): array
    {
        $unpaid = (string) SalesInvoice::query()
            ->whereIn('status', [InvoiceStatus::Issued, InvoiceStatus::Overdue])
            ->sum('due_amount');

        $partial = (string) SalesInvoice::query()
            ->where('status', InvoiceStatus::PartiallyPaid)
            ->sum('due_amount');

        return [
            'unpaid_due_total' => number_format((float) $unpaid, 2, '.', ''),
            'partially_paid_due_total' => number_format((float) $partial, 2, '.', ''),
        ];
    }

    /**
     * Low stock يعتمد على ERP: stock_balances.quantity_on_hand مقابل inventory_items.minimum_stock.
     *
     * @return array{low_stock_count: int, out_of_stock_count: int}
     */
    public function inventoryStats(): array
    {
        $lowStock = (int) StockBalance::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'stock_balances.inventory_item_id')
            ->where('inventory_items.is_active', true)
            ->where('inventory_items.minimum_stock', '>', 0)
            ->whereColumn('stock_balances.quantity_on_hand', '<', 'inventory_items.minimum_stock')
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->count();

        $outOfStock = (int) StockBalance::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'stock_balances.inventory_item_id')
            ->where('inventory_items.is_active', true)
            ->where('stock_balances.quantity_on_hand', '<=', 0)
            ->count();

        return [
            'low_stock_count' => $lowStock,
            'out_of_stock_count' => $outOfStock,
        ];
    }

    /**
     * حالات الحضور متبادلة: present / late / absent بشكل منفصل.
     *
     * @return array{present: int, late: int, absent: int}
     */
    public function hrAttendanceStats(?Carbon $now = null): array
    {
        $now ??= now();
        $today = $now->toDateString();

        $rows = HrAttendanceRecord::query()
            ->whereDate('attendance_date', $today)
            ->whereIn('status', [
                AttendanceStatus::Present,
                AttendanceStatus::Late,
                AttendanceStatus::Absent,
            ])
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->get();

        $counts = [
            AttendanceStatus::Present->value => 0,
            AttendanceStatus::Late->value => 0,
            AttendanceStatus::Absent->value => 0,
        ];

        foreach ($rows as $row) {
            $key = $row->status instanceof AttendanceStatus
                ? $row->status->value
                : (string) $row->getAttributes()['status'];
            if (array_key_exists($key, $counts)) {
                $counts[$key] = (int) $row->cnt;
            }
        }

        return [
            'present' => $counts[AttendanceStatus::Present->value],
            'late' => $counts[AttendanceStatus::Late->value],
            'absent' => $counts[AttendanceStatus::Absent->value],
        ];
    }

    /**
     * إجمالي المبيعات اليومية لآخر N أيام (يشمل الأصفار).
     *
     * @return array{labels: list<string>, values: list<float>, dates: list<string>}
     */
    public function salesChart(int $days = 7, ?Carbon $now = null): array
    {
        $now ??= now();
        $start = $now->copy()->subDays($days - 1)->startOfDay();

        $rows = $this->countableSalesQuery()
            ->whereDate('sale_date', '>=', $start->toDateString())
            ->whereDate('sale_date', '<=', $now->toDateString())
            ->selectRaw('DATE(sale_date) as d, COALESCE(SUM(grand_total), 0) as total')
            ->groupByRaw('DATE(sale_date)')
            ->pluck('total', 'd');

        $labels = [];
        $values = [];
        $dates = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $key = $day->toDateString();
            $dates[] = $key;
            $labels[] = $day->format('d/m');
            $raw = $rows[$key] ?? $rows[$day->format('Y-m-d')] ?? 0;
            $values[] = round((float) $raw, 2);
        }

        return compact('labels', 'values', 'dates');
    }

    /**
     * @return Collection<int, Sale>
     */
    public function latestSales(int $limit = 5): Collection
    {
        return $this->countableSalesQuery()
            ->with(['customer:id,name'])
            ->orderByDesc('sale_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get([
                'id',
                'document_number',
                'receipt_number',
                'source_type',
                'customer_id',
                'sale_date',
                'status',
                'grand_total',
                'currency_code',
                'created_at',
            ]);
    }

    /**
     * @return Collection<int, object>
     */
    public function lowStockItems(int $limit = 5): Collection
    {
        return StockBalance::query()
            ->join('inventory_items', 'inventory_items.id', '=', 'stock_balances.inventory_item_id')
            ->join('warehouses', 'warehouses.id', '=', 'stock_balances.warehouse_id')
            ->where('inventory_items.is_active', true)
            ->where('inventory_items.minimum_stock', '>', 0)
            ->whereColumn('stock_balances.quantity_on_hand', '<', 'inventory_items.minimum_stock')
            ->where('stock_balances.quantity_on_hand', '>', 0)
            ->orderBy('stock_balances.quantity_on_hand')
            ->limit($limit)
            ->get([
                'stock_balances.id',
                'inventory_items.id as inventory_item_id',
                'inventory_items.name as item_name',
                'inventory_items.sku',
                'inventory_items.barcode',
                'inventory_items.minimum_stock',
                'stock_balances.quantity_on_hand',
                'warehouses.name as warehouse_name',
            ]);
    }

    /**
     * @return Collection<int, HrAttendanceRecord>
     */
    public function attendanceToday(int $limit = 5, ?Carbon $now = null): Collection
    {
        $now ??= now();

        return HrAttendanceRecord::query()
            ->with(['employee:id,full_name,employee_number'])
            ->whereDate('attendance_date', $now->toDateString())
            ->whereIn('status', [
                AttendanceStatus::Present,
                AttendanceStatus::Late,
                AttendanceStatus::Absent,
                AttendanceStatus::Incomplete,
            ])
            ->orderByRaw("CASE status WHEN 'late' THEN 0 WHEN 'absent' THEN 1 WHEN 'incomplete' THEN 2 ELSE 3 END")
            ->orderByDesc('check_in_at')
            ->limit($limit)
            ->get([
                'id',
                'employee_id',
                'attendance_date',
                'status',
                'check_in_at',
                'check_out_at',
                'late_minutes',
            ]);
    }
}
