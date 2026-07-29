<?php

namespace Tests\Feature\Pos;

use App\Enums\Commerce\CatalogProductType;
use App\Enums\Commerce\ProductStatus;
use App\Enums\Commerce\ProductVisibility;
use App\Enums\Erp\SaleStatus;
use App\Enums\Pos\CashierSessionStatus;
use App\Enums\Pos\ReceiptNumberStrategy;
use App\Models\Tenant\CashDrawer;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\PosSetting;
use App\Models\Tenant\Product;
use App\Services\Pos\PosTerminalService;
use Filament\Facades\Filament;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Erp\ErpTestCase;

class PosInterfaceTest extends ErpTestCase
{
    private CashDrawer $drawer;

    private PosRegister $register;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('tenant'));

        $this->drawer = CashDrawer::query()->create([
            'branch_id' => $this->branch->id,
            'name' => 'POS Drawer',
            'code' => 'DR-POS',
            'is_active' => true,
        ]);

        $this->register = PosRegister::query()->create([
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'cash_drawer_id' => $this->drawer->id,
            'name' => 'POS Register',
            'code' => 'POS1',
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

        $this->product = Product::query()->create([
            'name' => 'POS Coffee',
            'slug' => 'pos-coffee-'.str()->random(4),
            'sku' => 'COF-1',
            'barcode' => '6281000000001',
            'price' => 25,
            'sale_price' => 25,
            'quantity' => 10,
            'track_stock' => true,
            'disable_orders_for_no_stock' => true,
            'catalog_type' => CatalogProductType::InventoryItem,
            'status' => ProductStatus::Active,
            'visibility' => ProductVisibility::Visible,
            'type' => 'physical',
            'is_active' => true,
            'unit_id' => $this->unit->id,
        ]);
    }

    public function test_pos_page_renders_blade_shell(): void
    {
        $this->getOnTenant(route('filament.tenant.pos', absolute: false))
            ->assertOk()
            ->assertSee('id="pos-app"', false)
            ->assertSee('data-api-base', false);
    }

    public function test_bootstrap_endpoint(): void
    {
        $this->getOnTenant($this->api('bootstrap'))
            ->assertOk()
            ->assertJsonPath('data.register.id', $this->register->id)
            ->assertJsonPath('data.session', null)
            ->assertJsonStructure(['data' => ['user', 'registers', 'payment_methods', 'categories', 'settings']]);
    }

    public function test_open_session_and_status(): void
    {
        $session = app(PosTerminalService::class)->sessions()->open(
            $this->register,
            '100.00',
            'test-pos',
            'morning',
        );

        $this->assertSame(CashierSessionStatus::Opened, $session->status);

        $this->getOnTenant($this->api('session.status').'?register_id='.$this->register->id)
            ->assertOk()
            ->assertJsonPath('data.session.status', CashierSessionStatus::Opened->value)
            ->assertJsonPath('data.session.id', $session->id);
    }

    public function test_cannot_checkout_without_open_session(): void
    {
        try {
            app(PosTerminalService::class)->checkout($this->checkoutPayload());
            $this->fail('Expected validation exception');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('session', $e->errors());
        }
    }

    public function test_products_search_and_barcode(): void
    {
        $this->openSession();

        $this->getOnTenant($this->api('products').'?search=Coffee')
            ->assertOk()
            ->assertJsonFragment(['sku' => 'COF-1']);

        $this->getOnTenant($this->api('barcode').'?code=6281000000001')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'COF-1');
    }

    public function test_successful_checkout(): void
    {
        $this->openSession();

        $result = app(PosTerminalService::class)->checkout($this->checkoutPayload([
            'payments' => [['type' => 'cash', 'amount' => '30.00']],
        ]));

        $this->assertSame(SaleStatus::Invoiced->value, $result['sale']['status']);
        $this->assertSame('5.00', $result['change']);

        $this->product->refresh();
        $this->assertSame(9, (int) $this->product->quantity);
    }

    public function test_insufficient_stock_rejected(): void
    {
        $this->openSession();

        $this->expectException(ValidationException::class);
        app(PosTerminalService::class)->checkout($this->checkoutPayload([
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 99,
                'discount' => 0,
                'tax' => 0,
            ]],
            'payments' => [['type' => 'cash', 'amount' => '2475']],
        ]));
    }

    public function test_invalid_payment_rejected(): void
    {
        $this->openSession();

        $this->expectException(ValidationException::class);
        app(PosTerminalService::class)->checkout($this->checkoutPayload([
            'payments' => [['type' => 'cash', 'amount' => '1.00']],
        ]));
    }

    public function test_suspend_resume_and_cancel(): void
    {
        $this->openSession();
        $terminal = app(PosTerminalService::class);
        $guarded = $terminal->assertCanOperate($this->register);

        $sale = $terminal->salesEngine()->createDraftSale(
            \App\Enums\Commerce\SaleChannel::Pos,
            [
                'pos_register_id' => $guarded['register']->id,
                'cashier_session_id' => $guarded['session']->id,
                'branch_id' => $guarded['register']->branch_id,
            ],
            $terminal->buildSaleItems([[
                'product_id' => $this->product->id,
                'quantity' => 1,
                'discount' => 0,
                'tax' => 0,
            ]]),
        );

        $suspended = $terminal->salesEngine()->suspend($sale);
        $this->assertTrue($suspended->is_suspended);

        $this->getOnTenant($this->api('suspended').'?register_id='.$this->register->id)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $resumed = $terminal->salesEngine()->resume($suspended);
        $this->assertFalse($resumed->is_suspended);

        $cancelled = $terminal->abandonPosSale($resumed, 'resumed into cart');
        $this->assertSame(SaleStatus::Cancelled, $cancelled->status);
    }

    public function test_cash_in_and_cash_out_via_service(): void
    {
        $this->openSession();

        $terminal = app(PosTerminalService::class);
        $guarded = $terminal->assertCanOperate($this->register);

        $in = $terminal->movements()->record(
            $guarded['session'],
            \App\Enums\Pos\CashMovementType::CashIn,
            '20.00',
            ['direction' => 'in', 'payment_method_type' => 'cash', 'reference' => 'float top-up']
        );
        $out = $terminal->movements()->record(
            $guarded['session'],
            \App\Enums\Pos\CashMovementType::CashOut,
            '5.00',
            ['direction' => 'out', 'payment_method_type' => 'cash', 'reference' => 'safe drop']
        );

        $this->assertNotNull($in->id);
        $this->assertNotNull($out->id);
    }

    public function test_close_shift_blocks_further_sales(): void
    {
        $this->openSession();

        $terminal = app(PosTerminalService::class);
        $guarded = $terminal->assertCanOperate($this->register);
        $closed = $terminal->sessions()->close($guarded['session'], [
            'cash' => '100',
            'card' => '0',
            'transfer' => '0',
            'other' => '0',
        ], 'done');

        $this->assertSame(CashierSessionStatus::Closed, $closed->status);

        $this->expectException(ValidationException::class);
        $terminal->checkout($this->checkoutPayload());
    }

    public function test_tenant_isolation_registers_are_scoped(): void
    {
        $this->getOnTenant($this->api('bootstrap'))
            ->assertOk()
            ->assertJsonPath('data.register.id', $this->register->id);

        $this->assertSame(1, PosRegister::query()->count());
    }

    public function test_store_checkout_route_still_registered(): void
    {
        $routes = collect(app('router')->getRoutes())->map(fn ($r) => $r->uri());
        $this->assertTrue($routes->contains(fn ($uri) => str_contains($uri, 'checkout/{token}')));
        $this->assertTrue($routes->contains(fn ($uri) => str_contains($uri, 'cart/{token}')));
    }

    public function test_receipt_view_after_checkout(): void
    {
        $this->openSession();
        $result = app(PosTerminalService::class)->checkout($this->checkoutPayload([
            'payments' => [['type' => 'cash', 'amount' => '25']],
        ]));

        $this->getOnTenant(route('filament.tenant.pos.receipt', ['sale' => $result['sale']['id']], absolute: false))
            ->assertOk()
            ->assertSee($result['sale']['receipt_number'] ?: $result['sale']['document_number']);
    }

    private function openSession(): void
    {
        app(PosTerminalService::class)->sessions()->open(
            $this->register,
            '100.00',
            'phpunit',
            null,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function checkoutPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'register_id' => $this->register->id,
            'idempotency_key' => 'pos-test-'.str()->uuid(),
            'discount_total' => 0,
            'items' => [[
                'product_id' => $this->product->id,
                'quantity' => 1,
                'discount' => 0,
                'tax' => 0,
            ]],
            'payments' => [['type' => 'cash', 'amount' => '25.00']],
        ], $overrides);
    }

    private function api(string $name, array $params = []): string
    {
        return route('filament.tenant.pos.api.'.$name, $params, absolute: false);
    }

    private function getOnTenant(string $uri)
    {
        $domain = $this->tenant->domains()->first()->domain;

        return $this->actingAs($this->user, 'tenant')
            ->withServerVariables(['HTTP_HOST' => $domain])
            ->get('http://'.$domain.$uri);
    }

    private function postOnTenant(string $uri, array $data = [])
    {
        $domain = $this->tenant->domains()->first()->domain;

        return $this->actingAs($this->user, 'tenant')
            ->withServerVariables(['HTTP_HOST' => $domain])
            ->postJson('http://'.$domain.$uri, $data);
    }
}
