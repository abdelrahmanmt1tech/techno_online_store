<?php

namespace Tests\Feature\Erp;

use App\Filament\Tenant\Resources\Branches\Pages\CreateBranch;
use App\Filament\Tenant\Resources\Branches\Pages\ListBranches;
use App\Filament\Tenant\Resources\Sales\Pages\CreateSale;
use App\Filament\Tenant\Resources\StockTransactions\Pages\CreateStockReceipt;
use App\Filament\Tenant\Resources\StockTransactions\Pages\ListStockReceipts;
use App\Models\Tenant\Branch;
use Filament\Facades\Filament;
use Livewire\Livewire;

class FilamentErpSmokeTest extends ErpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('tenant'));
    }

    public function test_branch_list_and_create_pages_work(): void
    {
        Livewire::actingAs($this->user, 'tenant')
            ->test(ListBranches::class)
            ->assertSuccessful();

        Livewire::actingAs($this->user, 'tenant')
            ->test(CreateBranch::class)
            ->fillForm([
                'name' => 'Filament Branch',
                'code' => 'BR-FIL',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('branches', [
            'code' => 'BR-FIL',
            'name' => 'Filament Branch',
        ], 'tenant');
    }

    public function test_stock_receipt_create_page_loads(): void
    {
        Livewire::actingAs($this->user, 'tenant')
            ->test(ListStockReceipts::class)
            ->assertSuccessful();

        Livewire::actingAs($this->user, 'tenant')
            ->test(CreateStockReceipt::class)
            ->assertSuccessful();
    }

    public function test_sale_create_page_loads(): void
    {
        Livewire::actingAs($this->user, 'tenant')
            ->test(CreateSale::class)
            ->assertSuccessful();
    }

    public function test_existing_branch_appears_in_table(): void
    {
        $branch = Branch::query()->create([
            'name' => 'Listed',
            'code' => 'BR-LIST',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->user, 'tenant')
            ->test(ListBranches::class)
            ->assertCanSeeTableRecords(collect([$branch]));
    }
}
