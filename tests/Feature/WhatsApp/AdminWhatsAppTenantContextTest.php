<?php

namespace Tests\Feature\WhatsApp;

use App\Filament\Pages\WhatsAppInboxPage;
use App\Filament\Pages\WhatsAppTemplatesPage;
use App\Models\Admin;
use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

class AdminWhatsAppTenantContextTest extends WhatsAppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Permission::findOrCreate('whatsapp.platform.view_all_conversations', 'admin');
        Permission::findOrCreate('whatsapp.platform.view_all_templates', 'admin');
    }

    public function test_clearing_selected_tenant_id_ends_tenant_context(): void
    {
        $tenant = $this->createTenantWithDatabase();
        $admin = $this->createAdmin();

        $page = $this->makeInboxPage($admin);
        $page->selectedTenantId = $tenant->id;
        $page->updatedSelectedTenantId();

        $this->assertTrue(tenancy()->initialized);

        $page->selectedTenantId = null;
        $page->updatedSelectedTenantId();

        $this->assertFalse(tenancy()->initialized);
    }

    public function test_admin_inbox_does_not_leave_tenant_context_when_tenant_selection_is_blank(): void
    {
        $tenant = $this->createTenantWithDatabase();
        $admin = $this->createAdmin();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
            ]);
        });

        $page = $this->makeInboxPage($admin);
        $page->selectedTenantId = $tenant->id;
        $page->updatedSelectedTenantId();

        $page->selectedTenantId = '';
        $page->updatedSelectedTenantId();

        $this->assertNull($page->selectedConversationId);
        $this->assertFalse(tenancy()->initialized);
    }

    public function test_switching_tenant_context_does_not_leak_previous_tenant(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();
        $admin = $this->createAdmin();

        $page = $this->makeInboxPage($admin);

        $page->selectedTenantId = $tenantA->id;
        $page->updatedSelectedTenantId();
        $this->assertSame($tenantA->id, tenant('id'));

        $page->selectedTenantId = $tenantB->id;
        $page->updatedSelectedTenantId();
        $this->assertSame($tenantB->id, tenant('id'));
    }

    public function test_templates_page_clears_tenant_context_when_selection_is_blank(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $page = new WhatsAppTemplatesPage;
        $initialize = new \ReflectionMethod($page, 'initializeTenant');
        $initialize->setAccessible(true);

        $page->selectedTenantId = $tenant->id;
        $initialize->invoke($page);

        $this->assertTrue(tenancy()->initialized);

        $page->selectedTenantId = null;
        $initialize->invoke($page);

        $this->assertFalse(tenancy()->initialized);
    }

    protected function makeInboxPage(Admin $admin): WhatsAppInboxPage
    {
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return Livewire::test(WhatsAppInboxPage::class)->instance();
    }

    protected function createAdmin(): Admin
    {
        $admin = Admin::query()->create([
            'name' => 'Admin User',
            'email' => 'admin-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->givePermissionTo('whatsapp.platform.view_all_conversations');

        return $admin;
    }
}
