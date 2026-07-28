<?php

namespace Tests\Feature\Pos;

use App\Actions\Pos\CloseCashierSessionAction;
use App\Actions\Pos\OpenCashierSessionAction;
use App\Enums\Commerce\SaleChannel;
use App\Enums\Pos\CashierSessionStatus;
use App\Enums\Pos\CashMovementType;
use App\Enums\Pos\ReceiptNumberStrategy;
use App\Exceptions\Pos\PosRegisterGuardException;
use App\Models\Tenant\CashDrawer;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\PosSetting;
use App\Services\Commerce\UnifiedSalesEngine;
use App\Services\Pos\CashierSessionService;
use App\Services\Pos\CashMovementService;
use App\Services\Pos\PosReceiptNumberService;
use App\Services\Pos\PosRegisterGuard;
use App\Services\Pos\ShiftReportService;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\Feature\Erp\ErpTestCase;

class PosRuntimeTest extends ErpTestCase
{
    private CashDrawer $drawer;

    private PosRegister $register;

    protected function setUp(): void
    {
        parent::setUp();

        $this->drawer = CashDrawer::query()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Drawer A',
            'code' => 'DR-A',
            'is_active' => true,
        ]);

        $this->register = PosRegister::query()->create([
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'cash_drawer_id' => $this->drawer->id,
            'name' => 'Front Register',
            'code' => 'FR1',
            'receipt_prefix' => 'POS',
            'is_active' => true,
        ]);

        PosSetting::query()->create([
            'receipt_number_strategy' => ReceiptNumberStrategy::BranchRegisterDate,
            'require_open_session' => true,
            'allow_suspend_sales' => true,
            'suspend_expires_minutes' => 60,
            'allow_negative_stock' => false,
        ]);
    }

    public function test_open_and_close_session_lifecycle(): void
    {
        $open = app(OpenCashierSessionAction::class)->execute($this->register, '50.00', 'POS-1', 'open notes');
        $this->assertSame(CashierSessionStatus::Opened, $open->status);
        $this->assertTrue($open->cashMovements()->where('type', CashMovementType::Opening)->exists());

        $closed = app(CloseCashierSessionAction::class)->execute($open, [
            'cash' => '50.00',
            'card' => '0',
            'transfer' => '0',
            'other' => '0',
        ], 'close notes');

        $this->assertSame(CashierSessionStatus::Closed, $closed->status);
        $this->assertSame('0.00', (string) $closed->difference);
        $this->assertNotNull($closed->expected_cash);
    }

    public function test_cannot_open_two_sessions_on_same_register(): void
    {
        app(OpenCashierSessionAction::class)->execute($this->register, '10.00');

        $this->expectException(ValidationException::class);
        app(OpenCashierSessionAction::class)->execute($this->register, '10.00');
    }

    public function test_cannot_open_two_sessions_for_same_user_on_different_registers(): void
    {
        $register2 = PosRegister::query()->create([
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'cash_drawer_id' => $this->drawer->id,
            'name' => 'Second',
            'code' => 'FR2',
            'is_active' => true,
        ]);

        app(OpenCashierSessionAction::class)->execute($this->register, '10.00');

        $this->expectException(ValidationException::class);
        app(OpenCashierSessionAction::class)->execute($register2, '10.00');
    }

    public function test_cash_movements_are_immutable_and_reversible(): void
    {
        $session = app(OpenCashierSessionAction::class)->execute($this->register, '100.00');
        $service = app(CashMovementService::class);

        $movement = $service->record($session, CashMovementType::CashOut, '20.00', [
            'payment_method_type' => 'cash',
            'notes' => 'petty cash',
        ]);

        $this->expectException(LogicException::class);
        $movement->update(['amount' => '1.00']);
    }

    public function test_cash_movement_reversal_creates_opposite_row(): void
    {
        $session = app(OpenCashierSessionAction::class)->execute($this->register, '100.00');
        $service = app(CashMovementService::class);
        $movement = $service->record($session, CashMovementType::CashIn, '15.00', [
            'payment_method_type' => 'cash',
        ]);

        $reversal = $service->reverse($movement);
        $this->assertTrue($reversal->is_reversal);
        $this->assertSame($movement->id, $reversal->reverses_movement_id);
        $this->assertSame('out', $reversal->direction);
    }

    public function test_suspend_resume_and_cancel_suspended(): void
    {
        $session = app(OpenCashierSessionAction::class)->execute($this->register, '20.00');
        $engine = app(UnifiedSalesEngine::class);

        $sale = $engine->createDraftSale(SaleChannel::Pos, [
            'pos_register_id' => $this->register->id,
            'cashier_session_id' => $session->id,
            'branch_id' => $this->branch->id,
        ], [
            [
                'source_type' => 'manual',
                'description_snapshot' => 'Item',
                'quantity' => 1,
                'unit_price' => 9,
            ],
        ]);

        $suspended = $engine->suspend($sale);
        $this->assertTrue($suspended->is_suspended);
        $this->assertNotNull($suspended->suspended_until);

        $resumed = $engine->resume($suspended);
        $this->assertFalse($resumed->is_suspended);

        $engine->suspend($resumed);
        $cancelled = $engine->cancelSuspended($resumed->fresh());
        $this->assertFalse($cancelled->is_suspended);
        $this->assertSame('cancelled', $cancelled->status->value);
    }

    public function test_receipt_numbers_use_branch_register_date_sequence(): void
    {
        $a = app(PosReceiptNumberService::class)->next($this->register);
        $b = app(PosReceiptNumberService::class)->next($this->register);

        $this->assertNotSame($a, $b);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+-[A-Z0-9]+-\d{8}-\d{4}$/', $a);
    }

    public function test_register_guard_blocks_without_session(): void
    {
        $this->expectException(PosRegisterGuardException::class);
        app(PosRegisterGuard::class)->assertCanOperate($this->register);
    }

    public function test_shift_reports_include_core_metrics(): void
    {
        $session = app(OpenCashierSessionAction::class)->execute($this->register, '30.00');
        $engine = app(UnifiedSalesEngine::class);
        $sale = $engine->createDraftSale(SaleChannel::Pos, [
            'pos_register_id' => $this->register->id,
            'cashier_session_id' => $session->id,
            'branch_id' => $this->branch->id,
        ], [
            [
                'source_type' => 'manual',
                'description_snapshot' => 'Item',
                'quantity' => 2,
                'unit_price' => 10,
                'tax' => 2,
                'discount' => 1,
            ],
        ]);
        $engine->confirm($sale);

        $report = app(ShiftReportService::class)->xReport($session->fresh());
        $this->assertSame('x', $report['report_type']);
        $this->assertSame(1, $report['sales_count']);
        $this->assertArrayHasKey('expected_by_tender', $report);
        $this->assertArrayHasKey('average_sale', $report);
    }

    public function test_cannot_close_session_twice(): void
    {
        $session = app(CashierSessionService::class)->open($this->register, '10.00');
        app(CashierSessionService::class)->close($session, '10.00', '10.00', 'done');

        $this->expectException(ValidationException::class);
        app(CashierSessionService::class)->close($session->fresh(), '10.00');
    }
}
