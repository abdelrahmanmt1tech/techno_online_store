<?php

namespace Tests\Feature\Erp;

use App\Actions\Erp\ConfirmSaleAction;
use App\Actions\Erp\CreatePurchaseInvoiceAction;
use App\Actions\Erp\CreateSalesInvoiceAction;
use App\Actions\Erp\PostGoodsReceiptAction;
use App\Actions\Erp\PostStockTransactionAction;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\InvoiceStatus;
use App\Enums\Erp\PurchaseLineType;
use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Filament\Tenant\Resources\InvoicePrintSettings\Pages\ManageInvoicePrintSettings;
use App\Filament\Tenant\Resources\SalesInvoices\Pages\ListSalesInvoices;
use App\Filament\Tenant\Resources\SalesInvoices\Pages\ViewSalesInvoice;
use App\Models\Tenant\Customer;
use App\Models\Tenant\GoodsReceipt;
use App\Models\Tenant\GoodsReceiptItem;
use App\Models\Tenant\InvoicePrintSetting;
use App\Models\Tenant\PurchaseInvoice;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\SalesInvoice;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Models\Tenant\Supplier;
use App\Services\Erp\DocumentNumberService;
use App\Services\Erp\InvoicePrintSettingsService;
use Filament\Facades\Filament;
use Livewire\Livewire;

class InvoicePrintingTest extends ErpTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('tenant'));
    }

    public function test_default_settings_created_once(): void
    {
        $service = app(InvoicePrintSettingsService::class);
        $a = $service->getOrCreate();
        $b = $service->getOrCreate();

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, InvoicePrintSetting::query()->count());
        $this->assertTrue($service->mergedDisplayOptions($a)['show_logo']);
    }

    public function test_settings_page_saves_display_options_and_company_name(): void
    {
        $settings = app(InvoicePrintSettingsService::class)->getOrCreate();

        Livewire::actingAs($this->user, 'tenant')
            ->test(ManageInvoicePrintSettings::class)
            ->assertSuccessful()
            ->fillForm([
                'company_name' => ['ar' => 'شركة اختبار', 'en' => 'Test Co'],
                'display_options' => array_merge(
                    app(InvoicePrintSettingsService::class)->defaultDisplayOptions(),
                    ['show_sku' => false, 'show_website' => true],
                ),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings->refresh();
        $this->assertSame('Test Co', $settings->getTranslation('company_name', 'en'));
        $this->assertFalse($settings->display_options['show_sku']);
        $this->assertTrue($settings->display_options['show_website']);
    }

    protected function printUrl(string $name, array $parameters = []): string
    {
        return route($name, $parameters, absolute: false);
    }

    protected function getOnTenant(string $uri)
    {
        $domain = $this->tenant->domains()->first()->domain;

        return $this->withServerVariables(['HTTP_HOST' => $domain])
            ->get('http://'.$domain.$uri);
    }

    public function test_sales_invoice_print_requires_auth_and_renders_content(): void
    {
        $invoice = $this->makeIssuedSalesInvoice();
        $uri = $this->printUrl('filament.tenant.erp.sales-invoices.print', ['salesInvoice' => $invoice]);

        auth('tenant')->logout();
        $this->getOnTenant($uri)->assertRedirect();

        $response = $this->actingAs($this->user, 'tenant')
            ->getOnTenant($uri.'?locale=en');

        $response->assertOk();
        $response->assertSee($invoice->document_number, false);
        $response->assertSee('Buyer Customer', false);
        $response->assertSee('Widget Line', false);
        $response->assertSee('200.00', false);
        $response->assertSee('dir="ltr"', false);
    }

    public function test_sales_invoice_print_arabic_is_rtl_and_hides_sku_when_disabled(): void
    {
        $settings = app(InvoicePrintSettingsService::class)->getOrCreate();
        $settings->display_options = array_merge(
            app(InvoicePrintSettingsService::class)->defaultDisplayOptions(),
            ['show_sku' => false],
        );
        $settings->default_locale = 'ar';
        $settings->save();

        $invoice = $this->makeIssuedSalesInvoice(['sku' => 'HIDE-SKU']);
        $uri = $this->printUrl('filament.tenant.erp.sales-invoices.print', [
            'salesInvoice' => $invoice,
        ]);

        $response = $this->actingAs($this->user, 'tenant')
            ->getOnTenant($uri.'?locale=ar');

        $response->assertOk();
        $response->assertSee('dir="rtl"', false);
        $response->assertDontSee('HIDE-SKU', false);
    }

    public function test_draft_invoice_shows_watermark_and_issued_snapshot_is_stable(): void
    {
        $draft = SalesInvoice::query()->create([
            'document_number' => 'INV-DRAFT-1',
            'sale_id' => $this->makeConfirmedSale()->id,
            'invoice_date' => now()->toDateString(),
            'status' => InvoiceStatus::Draft->value,
            'subtotal' => 10,
            'grand_total' => 10,
            'due_amount' => 10,
            'paid_amount' => 0,
        ]);

        $this->actingAs($this->user, 'tenant')
            ->getOnTenant($this->printUrl('filament.tenant.erp.sales-invoices.print', ['salesInvoice' => $draft]).'?locale=en')
            ->assertOk()
            ->assertSee('DRAFT', false);

        $settings = app(InvoicePrintSettingsService::class)->getOrCreate();
        $settings->setTranslation('company_name', 'en', 'Old Company');
        $settings->save();

        $issued = $this->makeIssuedSalesInvoice();
        $this->assertNotEmpty($issued->print_settings_snapshot);
        $this->assertSame('Old Company', $issued->print_settings_snapshot['company_name']['en']);

        $settings->setTranslation('company_name', 'en', 'New Company');
        $settings->save();

        $this->actingAs($this->user, 'tenant')
            ->getOnTenant($this->printUrl('filament.tenant.erp.sales-invoices.print', ['salesInvoice' => $issued]).'?locale=en')
            ->assertOk()
            ->assertSee('Old Company', false)
            ->assertDontSee('New Company', false);

        $fresh = $this->makeIssuedSalesInvoice();
        $this->actingAs($this->user, 'tenant')
            ->getOnTenant($this->printUrl('filament.tenant.erp.sales-invoices.print', ['salesInvoice' => $fresh]).'?locale=en')
            ->assertOk()
            ->assertSee('New Company', false);
    }

    public function test_purchase_invoice_print_shows_supplier_and_totals(): void
    {
        $invoice = $this->makeIssuedPurchaseInvoice();

        $this->actingAs($this->user, 'tenant')
            ->getOnTenant($this->printUrl('filament.tenant.erp.purchase-invoices.print', ['purchaseInvoice' => $invoice]).'?locale=en')
            ->assertOk()
            ->assertSee($invoice->document_number, false)
            ->assertSee('Print Supplier', false)
            ->assertSee((string) $invoice->grand_total, false);
    }

    public function test_filament_print_action_present_on_sales_list_and_view(): void
    {
        $invoice = $this->makeIssuedSalesInvoice();

        Livewire::actingAs($this->user, 'tenant')
            ->test(ListSalesInvoices::class)
            ->assertSuccessful()
            ->assertTableActionExists('printInvoice');

        Livewire::actingAs($this->user, 'tenant')
            ->test(ViewSalesInvoice::class, ['record' => $invoice->getKey()])
            ->assertSuccessful()
            ->assertActionExists('printInvoice');
    }

    private function makeConfirmedSale(): Sale
    {
        $item = $this->makeItem();
        $this->receipt($item->id, '20', '10');

        $customer = Customer::query()->create(['name' => 'Buyer Customer']);

        $sale = Sale::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::Sale),
            'source_type' => 'manual',
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'sale_date' => now()->toDateString(),
            'status' => SaleStatus::Draft->value,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Inventory->value,
            'inventory_item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'description_snapshot' => 'Widget Line',
            'sku_snapshot' => 'SKU-W',
            'quantity' => '2',
            'unit_price' => '100',
            'line_total' => 200,
        ]);

        return app(ConfirmSaleAction::class)->execute($sale);
    }

    /**
     * @param  array{sku?: string}  $overrides
     */
    private function makeIssuedSalesInvoice(array $overrides = []): SalesInvoice
    {
        $sale = $this->makeConfirmedSale();
        if (isset($overrides['sku'])) {
            $sale->items()->first()->update(['sku_snapshot' => $overrides['sku']]);
        }

        return app(CreateSalesInvoiceAction::class)->execute($sale->fresh('items'));
    }

    private function makeIssuedPurchaseInvoice(): PurchaseInvoice
    {
        $item = $this->makeItem();
        $supplier = Supplier::query()->create(['name' => 'Print Supplier', 'is_active' => true]);

        $gr = GoodsReceipt::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::GoodsReceipt),
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft->value,
        ]);

        GoodsReceiptItem::query()->create([
            'goods_receipt_id' => $gr->id,
            'line_type' => PurchaseLineType::Inventory->value,
            'inventory_item_id' => $item->id,
            'description_snapshot' => 'Purchased part',
            'sku_snapshot' => 'P-1',
            'quantity' => '3',
            'unit_cost' => '40',
            'total_cost' => 120,
        ]);

        app(PostGoodsReceiptAction::class)->execute($gr);

        return app(CreatePurchaseInvoiceAction::class)->execute($gr->fresh());
    }

    private function receipt(int $itemId, string $qty, string $cost): void
    {
        $tx = StockTransaction::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::StockReceipt),
            'transaction_type' => StockTransactionType::ManualReceipt->value,
            'status' => DocumentStatus::Draft->value,
            'destination_warehouse_id' => $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
        ]);
        StockTransactionLine::query()->create([
            'stock_transaction_id' => $tx->id,
            'inventory_item_id' => $itemId,
            'source_kind' => StockLineSourceKind::Inventory->value,
            'quantity' => $qty,
            'unit_cost' => $cost,
        ]);
        app(PostStockTransactionAction::class)->execute($tx);
    }
}
