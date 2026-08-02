<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\ClientStage;
use App\Filament\Crm\Pages\LeadClients;
use App\Models\Tenant;
use App\Models\Tenant\Client;
use App\Models\Tenant\LeadSource;
use App\Models\Tenant\Opportunity;
use App\Models\Tenant\OpportunityStage;
use App\Models\TenantUser;
use Database\Seeders\FollowUpStatusSeeder;
use Database\Seeders\FollowUpTypeSeeder;
use Database\Seeders\LeadSourceSeeder;
use Database\Seeders\OpportunityStageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Filament\Facades\Filament;
use Tests\TestCase;

class CrmFoundationSmokeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    protected $connectionsToTransact = [];

    protected Tenant $tenant;

    protected TenantUser $user;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::setUp();

        $this->tenant = $this->createTenantWithDatabase();
        tenancy()->initialize($this->tenant);

        $this->user = TenantUser::query()->create([
            'name' => 'CRM Admin',
            'email' => 'crm@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
            'commission_percentage' => 5,
        ]);
        $this->actingAs($this->user, 'tenant');
        Filament::setCurrentPanel(Filament::getPanel('crm'));

        (new LeadSourceSeeder)->run();
        (new OpportunityStageSeeder)->run();
        (new FollowUpTypeSeeder)->run();
        (new FollowUpStatusSeeder)->run();
    }

    public function test_crm_seeders_and_lead_client_pipeline_basics(): void
    {
        $this->assertGreaterThanOrEqual(9, LeadSource::query()->count());
        $this->assertTrue(LeadSource::query()->where('id', 6)->exists()); // Walk-in
        $this->assertGreaterThanOrEqual(1, OpportunityStage::query()->count());

        $client = Client::query()->create([
            'name' => 'Lead One',
            'stage' => ClientStage::LEAD,
            'lead_source_id' => 6,
            'sales_rep_id' => $this->user->id,
        ]);

        $this->assertNull($client->fresh()->account_tree_id); // LEAD skips accTree

        $stage = OpportunityStage::query()->orderBy('sort_order')->firstOrFail();
        $opp = Opportunity::query()->create([
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'opportunity_stage_id' => $stage->id,
            'title' => 'Demo deal',
            'amount' => 1500,
            'assigned_to' => $this->user->id,
        ]);

        $this->assertSame('Demo deal', $opp->fresh()->title);
        $this->assertSame(5.0, (float) $this->user->fresh()->commission_percentage);

        Livewire::test(LeadClients::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$client]);
    }

    protected function createTenantWithDatabase(): Tenant
    {
        $tenant = Tenant::query()->create([
            'id' => (string) str()->uuid(),
            'name' => 'CRM Test Store',
            'email' => 'crm-store@example.com',
            'is_active' => true,
        ]);

        $tenant->domains()->create(['domain' => 'crm-'.$tenant->id.'.localhost']);

        return $tenant->fresh();
    }
}
