<?php

namespace Tests\Feature\Dashboard;

use App\Enums\Erp\CostingMethod;
use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\InvoiceStatus;
use App\Enums\Erp\PaymentMethod;
use App\Enums\Erp\SaleSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Hr\AttendanceStatus;
use App\Enums\Hr\EmploymentStatus;
use App\Enums\Hr\SalaryType;
use App\Enums\Pos\CashierSessionStatus;
use App\Filament\Tenant\Pages\Dashboard;
use App\Filament\Tenant\Widgets\AttendanceTodayWidget;
use App\Filament\Tenant\Widgets\HrAttendanceStatsWidget;
use App\Filament\Tenant\Widgets\LatestSalesWidget;
use App\Filament\Tenant\Widgets\PosInventoryStatsWidget;
use App\Filament\Tenant\Widgets\SalesChartWidget;
use App\Filament\Tenant\Widgets\SalesCollectionStatsWidget;
use App\Filament\Tenant\Widgets\StoreKpis;
use App\Models\Tenant\CashDrawer;
use App\Models\Tenant\CashierSession;
use App\Models\Tenant\HrAttendanceRecord;
use App\Models\Tenant\HrEmployee;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SalesInvoice;
use App\Models\Tenant\StockBalance;
use App\Models\TenantUser;
use App\Services\Dashboard\DashboardMetricsService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Erp\ErpTestCase;

class DashboardLiteTest extends ErpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('tenant'));
        config(['app.bypass_permissions' => false]);
    }

    public function test_dashboard_requires_dashboard_view_permission(): void
    {
        $user = $this->makeUser([]);

        $this->actingAs($user, 'tenant');

        $this->assertFalse(Dashboard::canAccess());
    }

    public function test_dashboard_accessible_with_dashboard_view(): void
    {
        $user = $this->makeUser(['dashboard.view']);

        $this->actingAs($user, 'tenant');

        $this->assertTrue(Dashboard::canAccess());
    }

    public function test_widget_visibility_follows_permissions(): void
    {
        $salesOnly = $this->makeUser(['dashboard.view', 'dashboard.sales.view']);
        $this->actingAs($salesOnly, 'tenant');

        $this->assertTrue(SalesCollectionStatsWidget::canView());
        $this->assertTrue(SalesChartWidget::canView());
        $this->assertTrue(LatestSalesWidget::canView());
        $this->assertFalse(HrAttendanceStatsWidget::canView());
        $this->assertFalse(PosInventoryStatsWidget::canView());
        $this->assertFalse(StoreKpis::canView());

        $hrOnly = $this->makeUser(['dashboard.view', 'dashboard.hr.view']);
        $this->actingAs($hrOnly, 'tenant');

        $this->assertTrue(HrAttendanceStatsWidget::canView());
        $this->assertTrue(AttendanceTodayWidget::canView());
        $this->assertFalse(SalesCollectionStatsWidget::canView());
    }

    public function test_empty_tenant_metrics_are_zero(): void
    {
        $metrics = app(DashboardMetricsService::class);

        $sales = $metrics->salesStats();
        $pos = $metrics->posStats();
        $collection = $metrics->collectionStats();
        $inventory = $metrics->inventoryStats();
        $hr = $metrics->hrAttendanceStats();
        $chart = $metrics->salesChart(7);

        $this->assertSame('0.00', $sales['sales_today_total']);
        $this->assertSame(0, $sales['sales_today_count']);
        $this->assertSame(0, $pos['pos_today_count']);
        $this->assertSame('0.00', $collection['unpaid_due_total']);
        $this->assertSame(0, $inventory['low_stock_count']);
        $this->assertSame(0, $hr['present']);
        $this->assertCount(7, $chart['values']);
        $this->assertSame([0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0], $chart['values']);
    }

    public function test_sales_metrics_and_no_double_count_with_invoice(): void
    {
        $sale = Sale::query()->create([
            'document_number' => 'S-1',
            'source_type' => SaleSourceType::Pos,
            'branch_id' => $this->branch->id,
            'sale_date' => now()->toDateString(),
            'status' => SaleStatus::Invoiced,
            'currency_code' => 'EGP',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100,
            'is_suspended' => false,
        ]);

        SalesInvoice::query()->create([
            'document_number' => 'INV-1',
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->toDateString(),
            'status' => InvoiceStatus::Issued,
            'currency_code' => 'EGP',
            'subtotal' => 100,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 100,
            'paid_amount' => 0,
            'due_amount' => 100,
        ]);

        $metrics = app(DashboardMetricsService::class)->salesStats();

        // مصدر الحقيقة sales فقط — لا نجمع الفاتورة معها
        $this->assertSame('100.00', $metrics['sales_today_total']);
        $this->assertSame(1, $metrics['sales_today_count']);
    }

    public function test_collection_and_pos_collected_use_payments(): void
    {
        $sale = Sale::query()->create([
            'document_number' => 'S-POS',
            'source_type' => SaleSourceType::Pos,
            'branch_id' => $this->branch->id,
            'sale_date' => now()->toDateString(),
            'status' => SaleStatus::Invoiced,
            'currency_code' => 'EGP',
            'subtotal' => 200,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 200,
            'is_suspended' => false,
        ]);

        $invoice = SalesInvoice::query()->create([
            'document_number' => 'INV-POS',
            'sale_id' => $sale->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->toDateString(),
            'status' => InvoiceStatus::PartiallyPaid,
            'currency_code' => 'EGP',
            'subtotal' => 200,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 200,
            'paid_amount' => 50,
            'due_amount' => 150,
        ]);

        InvoicePayment::query()->create([
            'document_number' => 'PAY-1',
            'payable_type' => InvoicePayableType::SalesInvoice,
            'payable_id' => $invoice->id,
            'payment_method' => PaymentMethod::Cash,
            'amount' => 50,
            'paid_at' => now(),
            'status' => 'posted',
        ]);

        SalesInvoice::query()->create([
            'document_number' => 'INV-UNPAID',
            'sale_id' => Sale::query()->create([
                'document_number' => 'S-UNPAID',
                'source_type' => SaleSourceType::Manual,
                'branch_id' => $this->branch->id,
                'sale_date' => now()->toDateString(),
                'status' => SaleStatus::Invoiced,
                'currency_code' => 'EGP',
                'subtotal' => 80,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 80,
                'is_suspended' => false,
            ])->id,
            'branch_id' => $this->branch->id,
            'invoice_date' => now()->toDateString(),
            'status' => InvoiceStatus::Issued,
            'currency_code' => 'EGP',
            'subtotal' => 80,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 80,
            'paid_amount' => 0,
            'due_amount' => 80,
        ]);

        $metrics = app(DashboardMetricsService::class);
        $collection = $metrics->collectionStats();
        $pos = $metrics->posStats();

        $this->assertSame('80.00', $collection['unpaid_due_total']);
        $this->assertSame('150.00', $collection['partially_paid_due_total']);
        $this->assertSame('50.00', $pos['pos_today_collected']);
        $this->assertSame(1, $pos['pos_today_count']);
    }

    public function test_open_shifts_and_low_stock_and_hr_counts(): void
    {
        $drawer = CashDrawer::query()->create([
            'branch_id' => $this->branch->id,
            'name' => 'D1',
            'code' => 'D1',
            'is_active' => true,
        ]);

        $register = PosRegister::query()->create([
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'cash_drawer_id' => $drawer->id,
            'name' => 'R1',
            'code' => 'R1',
            'receipt_prefix' => 'R',
            'is_active' => true,
        ]);

        CashierSession::query()->create([
            'pos_register_id' => $register->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'status' => CashierSessionStatus::Opened,
            'opening_balance' => 0,
            'opened_at' => now(),
        ]);

        $item = InventoryItem::query()->create([
            'name' => 'Low Item',
            'sku' => 'LOW-1',
            'item_type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'costing_method' => CostingMethod::Fifo,
            'track_stock' => true,
            'minimum_stock' => 10,
            'is_active' => true,
        ]);

        StockBalance::query()->create([
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => $item->id,
            'quantity_on_hand' => 3,
        ]);

        StockBalance::query()->create([
            'warehouse_id' => $this->warehouse->id,
            'inventory_item_id' => InventoryItem::query()->create([
                'name' => 'Out Item',
                'sku' => 'OUT-1',
                'item_type' => 'finished_good',
                'unit_id' => $this->unit->id,
                'costing_method' => CostingMethod::Fifo,
                'track_stock' => true,
                'minimum_stock' => 1,
                'is_active' => true,
            ])->id,
            'quantity_on_hand' => 0,
        ]);

        $employee = HrEmployee::query()->create([
            'employee_number' => 'E-D1',
            'full_name' => 'Dash Emp',
            'branch_id' => $this->branch->id,
            'hire_date' => '2026-01-01',
            'employment_status' => EmploymentStatus::Active,
            'salary_type' => SalaryType::Monthly,
            'base_salary' => 1000,
            'is_active' => true,
        ]);

        HrAttendanceRecord::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::Present,
            'late_minutes' => 0,
        ]);

        HrAttendanceRecord::query()->create([
            'employee_id' => HrEmployee::query()->create([
                'employee_number' => 'E-D2',
                'full_name' => 'Late Emp',
                'branch_id' => $this->branch->id,
                'hire_date' => '2026-01-01',
                'employment_status' => EmploymentStatus::Active,
                'salary_type' => SalaryType::Monthly,
                'base_salary' => 1000,
                'is_active' => true,
            ])->id,
            'attendance_date' => now()->toDateString(),
            'status' => AttendanceStatus::Late,
            'late_minutes' => 12,
        ]);

        $metrics = app(DashboardMetricsService::class);

        $this->assertSame(1, $metrics->posStats()['open_shifts']);
        $this->assertSame(1, $metrics->inventoryStats()['low_stock_count']);
        $this->assertSame(1, $metrics->inventoryStats()['out_of_stock_count']);
        $this->assertSame(1, $metrics->hrAttendanceStats()['present']);
        $this->assertSame(1, $metrics->hrAttendanceStats()['late']);
        $this->assertSame(1, $metrics->lowStockItems(5)->count());
        $this->assertLessThanOrEqual(5, $metrics->attendanceToday(5)->count());
    }

    public function test_sales_chart_includes_zero_days_and_lists_capped_at_five(): void
    {
        for ($i = 0; $i < 7; $i++) {
            Sale::query()->create([
                'document_number' => 'S-'.$i,
                'source_type' => SaleSourceType::Manual,
                'branch_id' => $this->branch->id,
                'sale_date' => now()->toDateString(),
                'status' => SaleStatus::Confirmed,
                'currency_code' => 'EGP',
                'subtotal' => 10,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 10,
                'is_suspended' => false,
            ]);
        }

        $metrics = app(DashboardMetricsService::class);
        $chart = $metrics->salesChart(7);
        $latest = $metrics->latestSales(5);

        $this->assertCount(7, $chart['values']);
        $this->assertEqualsWithDelta(70.0, (float) end($chart['values']), 0.001);
        $this->assertCount(5, $latest);
    }

    public function test_dashboard_livewire_renders_for_authorized_user(): void
    {
        $user = $this->makeUser([
            'dashboard.view',
            'dashboard.sales.view',
            'dashboard.pos.view',
            'dashboard.inventory.view',
            'dashboard.hr.view',
        ]);

        $this->actingAs($user, 'tenant');

        Livewire::test(Dashboard::class)
            ->assertSuccessful();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function makeUser(array $permissions): TenantUser
    {
        $user = TenantUser::query()->create([
            'name' => 'Dash User',
            'email' => 'dash-'.str()->uuid().'@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        foreach ($permissions as $permission) {
            $model = Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'tenant',
            ]);
            $user->permissions()->syncWithoutDetaching([$model->id]);
        }

        return $user;
    }
}
