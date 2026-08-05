<?php

namespace Tests\Feature\Modules;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\TenantPackage;
use App\Support\Modules\TenantModule;
use App\Support\Modules\TenantModuleGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\TestCase;

class TenantModuleGateTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::setUp();

        config(['app.bypass_permissions' => false]);

        $this->tenant = Tenant::create([
            'id' => (string) str()->uuid(),
            'name' => 'Gate Test Store',
            'is_active' => true,
        ]);

        tenancy()->initialize($this->tenant);
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    private function makePackage(?string $module, bool $full = false): Package
    {
        return Package::create([
            'module' => $module,
            'is_full_package' => $full,
            'name' => ['ar' => 'باقة', 'en' => 'Package'],
            'trials_duration' => 0,
            'sort' => 0,
            'is_active' => true,
        ]);
    }

    private function subscribe(Package $package, array $overrides = []): TenantPackage
    {
        return $this->tenant->packages()->create(array_merge([
            'package_id' => $package->id,
            'price' => 100,
            'duration' => 1,
            'duration_type' => 'month',
            'started_at' => now()->subDay(),
            'trial_ends_at' => null,
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ], $overrides));
    }

    public function test_no_packages_grants_nothing(): void
    {
        foreach (TenantModule::cases() as $module) {
            $this->assertFalse(TenantModuleGate::enabled($module));
        }

        $this->assertSame([], TenantModuleGate::enabledKeys());
    }

    public function test_partial_package_grants_only_its_module(): void
    {
        $this->subscribe($this->makePackage('store'));

        $this->assertTrue(TenantModuleGate::enabled(TenantModule::Store));
        $this->assertTrue(tenant_module_enabled('store'));
        $this->assertFalse(TenantModuleGate::enabled(TenantModule::Pos));
        $this->assertFalse(TenantModuleGate::enabled(TenantModule::Crm));
        $this->assertFalse(TenantModuleGate::enabled(TenantModule::Accounting));
        $this->assertFalse(tenant_accounting_active());
        $this->assertSame(['store'], TenantModuleGate::enabledKeys());
    }

    public function test_full_package_grants_all_modules(): void
    {
        $this->subscribe($this->makePackage(null, true));

        $this->assertSame(
            ['store', 'pos', 'crm', 'accounting'],
            TenantModuleGate::enabledKeys()
        );
        $this->assertTrue(TenantModuleGate::accountingActive());
        $this->assertTrue(tenant_accounting_active());
    }

    public function test_multiple_partial_packages_accumulate(): void
    {
        $this->subscribe($this->makePackage('store'));
        $this->subscribe($this->makePackage('crm'));

        $this->assertTrue(TenantModuleGate::enabled(TenantModule::Store));
        $this->assertTrue(TenantModuleGate::enabled(TenantModule::Crm));
        $this->assertFalse(TenantModuleGate::enabled(TenantModule::Pos));
        $this->assertSame(['store', 'crm'], TenantModuleGate::enabledKeys());
    }

    public function test_expired_package_grants_nothing(): void
    {
        $store = $this->makePackage('store');
        $this->subscribe($store, [
            'expires_at' => now()->subDay(),
            'status' => 'expired',
        ]);

        $this->assertFalse(TenantModuleGate::enabled(TenantModule::Store));
        $this->assertSame([], TenantModuleGate::enabledKeys());
    }

    public function test_trial_package_grants_modules(): void
    {
        $this->subscribe($this->makePackage('pos'), [
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(3),
            'expires_at' => now()->addDays(3)->addMonth(),
        ]);

        $this->assertTrue(TenantModuleGate::enabled(TenantModule::Pos));
        $this->assertTrue(TenantModuleGate::enabled(TenantModule::Pos));
        $this->assertSame(['pos'], TenantModuleGate::enabledKeys());
    }

    public function test_cancelled_package_grants_nothing(): void
    {
        $this->subscribe($this->makePackage('store'), ['status' => 'cancelled']);

        $this->assertFalse(TenantModuleGate::enabled(TenantModule::Store));
    }

    public function test_bypass_permissions_opens_all_modules(): void
    {
        config(['app.bypass_permissions' => true]);

        foreach (TenantModule::cases() as $module) {
            $this->assertTrue(TenantModuleGate::enabled($module));
        }
    }

    public function test_any_enabled_matches_shared_catalog_store_or_pos(): void
    {
        $this->subscribe($this->makePackage('pos'));

        $this->assertTrue(TenantModuleGate::anyEnabled(TenantModule::Store, TenantModule::Pos));
        $this->assertTrue(tenant_module_any_enabled(TenantModule::Store, TenantModule::Pos));
        $this->assertFalse(TenantModuleGate::anyEnabled(TenantModule::Store, TenantModule::Crm));
        $this->assertFalse(tenant_module_any_enabled('store', 'crm'));
    }
}
