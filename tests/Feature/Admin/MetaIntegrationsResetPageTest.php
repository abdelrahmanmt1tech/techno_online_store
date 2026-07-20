<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\MetaIntegrationsReset;
use App\Models\Admin;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Messenger\MessengerTestCase;

class MetaIntegrationsResetPageTest extends MessengerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'meta.integration_reset_enabled' => true,
            'app.bypass_permissions' => true,
        ]);
    }

    public function test_guest_is_redirected(): void
    {
        $this->get('/admin/meta-integrations-reset')->assertRedirect();
    }

    public function test_authorized_admin_can_open_page(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(MetaIntegrationsReset::class)
            ->assertSuccessful()
            ->assertSee(__('dashboard.meta_reset_title'));
    }

    public function test_disabled_flag_shows_banner_and_blocks_preview(): void
    {
        config(['meta.integration_reset_enabled' => false]);

        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(MetaIntegrationsReset::class)
            ->assertSuccessful()
            ->assertSee(__('dashboard.meta_reset_disabled_title'))
            ->set('scope', 'all')
            ->call('runPreview')
            ->assertSet('preview', null);
    }

    public function test_unauthorized_admin_denied_when_bypass_off(): void
    {
        config(['app.bypass_permissions' => false]);
        Permission::findOrCreate('meta.integrations.reset', 'admin');

        // AppServiceProvider grants all abilities to admin id=1; use a later admin.
        Admin::query()->create([
            'name' => 'First Admin',
            'email' => 'first-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);

        $admin = $this->makeAdmin();
        $this->assertNotSame(1, $admin->id);
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertFalse(MetaIntegrationsReset::canAccess());
    }

    public function test_scope_change_invalidates_preview(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin, 'admin');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(MetaIntegrationsReset::class)
            ->set('scope', 'whatsapp')
            ->call('runPreview')
            ->assertNotSet('preview', null)
            ->set('scope', 'messenger')
            ->assertSet('preview', null);
    }

    protected function makeAdmin(): Admin
    {
        return Admin::query()->create([
            'name' => 'Page Admin',
            'email' => 'page-'.str()->uuid().'@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
    }
}
