<?php

namespace Tests\Unit\Support;

use App\Support\Modules\TenantModule;
use App\Support\Modules\TenantModuleGate;
use PHPUnit\Framework\TestCase;

class TenantModuleGateTest extends TestCase
{
    public function test_all_modules_currently_enabled(): void
    {
        foreach (TenantModule::cases() as $module) {
            $this->assertTrue(TenantModuleGate::enabled($module));
            $this->assertTrue(tenant_module_enabled($module->value));
        }

        $this->assertTrue(TenantModuleGate::accountingActive());
        $this->assertTrue(tenant_accounting_active());
        $this->assertSame(
            ['store', 'pos', 'crm', 'accounting'],
            TenantModuleGate::enabledKeys()
        );
    }
}
