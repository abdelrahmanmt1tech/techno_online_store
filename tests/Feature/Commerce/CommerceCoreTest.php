<?php

namespace Tests\Feature\Commerce;

use App\Enums\Commerce\CatalogProductType;
use App\Enums\Commerce\ProductStatus;
use App\Enums\Commerce\ProductVisibility;
use App\Enums\Commerce\SaleChannel;
use App\Enums\Erp\SaleStatus;
use App\Enums\Pos\CashierSessionStatus;
use App\Models\Tenant\Brand;
use App\Models\Tenant\CashDrawer;
use App\Models\Tenant\PosRegister;
use App\Models\Tenant\Product;
use App\Services\Commerce\CatalogService;
use App\Services\Commerce\UnifiedSalesEngine;
use App\Services\Pos\CashierSessionService;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Erp\ErpTestCase;

class CommerceCoreTest extends ErpTestCase
{
    public function test_shared_catalog_extends_existing_product_table(): void
    {
        $brand = Brand::query()->create([
            'name' => 'Acme',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'name' => 'Catalog Widget',
            'slug' => 'catalog-widget',
            'sku' => 'CW-1',
            'barcode' => '1234567890123',
            'brand_id' => $brand->id,
            'unit_id' => $this->unit->id,
            'price' => 100,
            'quantity' => 5,
            'track_stock' => true,
            'catalog_type' => CatalogProductType::InventoryItem,
            'status' => ProductStatus::Active,
            'visibility' => ProductVisibility::Visible,
            'type' => 'physical',
            'is_active' => true,
        ]);

        $product->refresh();

        $this->assertSame(CatalogProductType::InventoryItem, $product->catalog_type);
        $this->assertSame('physical', $product->type);
        $this->assertTrue($product->shouldTrackInventory());
        $this->assertSame($brand->id, $product->brand_id);
        $this->assertNotNull(app(CatalogService::class)->findBySkuOrBarcode('1234567890123'));
    }

    public function test_service_and_bundle_do_not_track_inventory_by_default(): void
    {
        $service = Product::query()->create([
            'name' => 'Install Service',
            'slug' => 'install-service',
            'sku' => 'SVC-1',
            'price' => 50,
            'quantity' => 0,
            'track_stock' => true,
            'catalog_type' => CatalogProductType::Service,
            'status' => ProductStatus::Active,
            'visibility' => ProductVisibility::Visible,
            'type' => 'physical',
            'is_active' => true,
        ]);

        $bundle = Product::query()->create([
            'name' => 'Starter Bundle',
            'slug' => 'starter-bundle',
            'sku' => 'BND-1',
            'price' => 200,
            'quantity' => 0,
            'track_stock' => true,
            'catalog_type' => CatalogProductType::Bundle,
            'status' => ProductStatus::Active,
            'visibility' => ProductVisibility::Visible,
            'type' => 'physical',
            'is_active' => true,
        ]);

        $this->assertFalse($service->shouldTrackInventory());
        $this->assertFalse(app(CatalogService::class)->allowsStockDeduction($bundle));
    }

    public function test_unified_sales_engine_creates_erp_sale_and_suspend_resume(): void
    {
        $engine = app(UnifiedSalesEngine::class);

        $sale = $engine->createDraftSale(SaleChannel::Erp, [
            'branch_id' => $this->branch->id,
            'notes' => 'engine draft',
        ], [
            [
                'source_type' => 'manual',
                'description_snapshot' => 'Manual line',
                'quantity' => 2,
                'unit_price' => 25,
                'unit_id' => $this->unit->id,
            ],
        ]);

        $this->assertSame(SaleStatus::Draft, $sale->status);
        $this->assertSame(1, $sale->items()->count());
        $this->assertFalse($sale->is_suspended);

        $suspended = $engine->suspend($sale);
        $this->assertTrue($suspended->is_suspended);

        $this->expectException(ValidationException::class);
        $engine->confirm($suspended);
    }

    public function test_unified_sales_engine_resume_then_confirm_manual_sale(): void
    {
        $engine = app(UnifiedSalesEngine::class);

        $sale = $engine->createDraftSale(SaleChannel::Erp, [
            'branch_id' => $this->branch->id,
        ], [
            [
                'source_type' => 'manual',
                'description_snapshot' => 'Manual line',
                'quantity' => 1,
                'unit_price' => 10,
                'unit_id' => $this->unit->id,
            ],
        ]);

        $engine->suspend($sale);
        $resumed = $engine->resume($sale->fresh());
        $this->assertFalse($resumed->is_suspended);

        $confirmed = $engine->confirm($resumed);
        $this->assertSame(SaleStatus::Confirmed, $confirmed->status);
    }

    public function test_pos_sale_requires_open_cashier_session(): void
    {
        $engine = app(UnifiedSalesEngine::class);

        $this->expectException(ValidationException::class);
        $engine->createDraftSale(SaleChannel::Pos, [
            'branch_id' => $this->branch->id,
        ], [
            [
                'source_type' => 'manual',
                'description_snapshot' => 'POS line',
                'quantity' => 1,
                'unit_price' => 5,
            ],
        ]);
    }

    public function test_cashier_session_open_and_close(): void
    {
        $drawer = CashDrawer::query()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Main Drawer',
            'code' => 'DR-1',
            'is_active' => true,
        ]);

        $register = PosRegister::query()->create([
            'branch_id' => $this->branch->id,
            'warehouse_id' => $this->warehouse->id,
            'cash_drawer_id' => $drawer->id,
            'name' => 'Register 1',
            'code' => 'R1',
            'receipt_prefix' => 'POS',
            'is_active' => true,
        ]);

        $sessions = app(CashierSessionService::class);
        $open = $sessions->open($register, '100.00', 'POS-PC-1', 'start');
        $this->assertSame(CashierSessionStatus::Opened, $open->status);

        $engine = app(UnifiedSalesEngine::class);
        $sale = $engine->createDraftSale(SaleChannel::Pos, [
            'branch_id' => $this->branch->id,
            'pos_register_id' => $register->id,
            'cashier_session_id' => $open->id,
        ], [
            [
                'source_type' => 'manual',
                'description_snapshot' => 'POS line',
                'quantity' => 1,
                'unit_price' => 15,
            ],
        ]);

        $this->assertSame($open->id, $sale->cashier_session_id);

        $closed = $sessions->close($open, '100.00', '100.00', 'end');
        $this->assertSame(CashierSessionStatus::Closed, $closed->status);
        $this->assertSame('0.00', (string) $closed->difference);
    }
}
