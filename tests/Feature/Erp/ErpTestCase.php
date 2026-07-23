<?php

namespace Tests\Feature\Erp;

use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\Product;
use App\Models\Tenant\UnitOfMeasure;
use App\Models\Tenant\Warehouse;
use App\Models\TenantUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

abstract class ErpTestCase extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    protected $connectionsToTransact = [];

    protected Tenant $tenant;

    protected TenantUser $user;

    protected UnitOfMeasure $unit;

    protected Warehouse $warehouse;

    protected Warehouse $warehouseB;

    protected Branch $branch;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::setUp();

        $this->tenant = $this->createTenantWithDatabase();
        tenancy()->initialize($this->tenant);

        $this->user = TenantUser::query()->create([
            'name' => 'ERP Admin',
            'email' => 'erp@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $this->actingAs($this->user, 'tenant');

        $this->branch = Branch::query()->create([
            'name' => 'Main Branch',
            'code' => 'BR-001',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->warehouse = Warehouse::query()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Main Warehouse',
            'code' => 'WH-001',
            'warehouse_type' => 'regular',
            'is_active' => true,
        ]);

        $this->warehouseB = Warehouse::query()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Secondary Warehouse',
            'code' => 'WH-002',
            'warehouse_type' => 'regular',
            'is_active' => true,
        ]);

        $this->unit = UnitOfMeasure::query()->create([
            'name' => 'Piece',
            'code' => 'PCS',
            'symbol' => 'pcs',
            'allows_decimal' => false,
            'precision' => 0,
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    protected function createTenantWithDatabase(): Tenant
    {
        $tenant = Tenant::query()->create([
            'id' => (string) str()->uuid(),
            'name' => 'ERP Test Store',
            'email' => 'erp-store@example.com',
            'is_active' => true,
        ]);

        $tenant->domains()->create(['domain' => 'erp-'.$tenant->id.'.localhost']);

        return $tenant->fresh();
    }

    protected function makeItem(string $name = 'Item A', bool $trackStock = true): InventoryItem
    {
        return InventoryItem::query()->create([
            'name' => $name,
            'sku' => 'SKU-'.str()->random(6),
            'item_type' => 'finished_good',
            'unit_id' => $this->unit->id,
            'costing_method' => 'fifo',
            'track_stock' => $trackStock,
            'is_active' => true,
        ]);
    }

    protected function makeSimpleProduct(string $name = 'Store Product', int $qty = 0): Product
    {
        return Product::query()->create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.str()->random(4),
            'sku' => 'P-'.str()->random(5),
            'price' => 50,
            'expense' => 20,
            'quantity' => $qty,
            'track_stock' => true,
            'disable_orders_for_no_stock' => true,
            'type' => 'physical',
            'is_active' => true,
        ]);
    }
}
