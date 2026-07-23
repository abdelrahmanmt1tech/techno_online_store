<?php

namespace Tests\Feature\Erp;

use App\Actions\Erp\ConfirmSaleAction;
use App\Actions\Erp\PostStockTransactionAction;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\CommerceQuantityAdjustment;
use App\Models\Tenant\InventoryItemCommerceLink;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\StockBalance;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Services\Erp\DocumentNumberService;
use Illuminate\Validation\ValidationException;

class CommerceAndSaleTest extends ErpTestCase
{
    public function test_commerce_receipt_increases_erp_and_store_once(): void
    {
        $product = $this->makeSimpleProduct('Simple', 2);

        $tx = StockTransaction::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::StockReceipt),
            'transaction_type' => StockTransactionType::ManualReceipt->value,
            'status' => DocumentStatus::Draft->value,
            'destination_warehouse_id' => $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
        ]);

        StockTransactionLine::query()->create([
            'stock_transaction_id' => $tx->id,
            'source_kind' => StockLineSourceKind::Commerce->value,
            'product_id' => $product->id,
            'quantity' => '5',
            'unit_cost' => '25',
            'affects_commerce_quantity' => true,
        ]);

        $posted = app(PostStockTransactionAction::class)->execute($tx);
        app(PostStockTransactionAction::class)->execute($posted); // idempotent

        $this->assertSame(7, $product->fresh()->quantity);
        $this->assertSame(1, CommerceQuantityAdjustment::query()->count());
        $this->assertSame('5.0000', (string) StockBalance::query()->value('quantity_on_hand'));
    }

    public function test_fractional_commerce_quantity_rejected(): void
    {
        $product = $this->makeSimpleProduct('Frac', 0);

        $tx = StockTransaction::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::StockReceipt),
            'transaction_type' => StockTransactionType::ManualReceipt->value,
            'status' => DocumentStatus::Draft->value,
            'destination_warehouse_id' => $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
        ]);

        StockTransactionLine::query()->create([
            'stock_transaction_id' => $tx->id,
            'source_kind' => StockLineSourceKind::Commerce->value,
            'product_id' => $product->id,
            'quantity' => '1.5',
            'unit_cost' => '10',
            'affects_commerce_quantity' => true,
        ]);

        $this->expectException(ValidationException::class);
        app(PostStockTransactionAction::class)->execute($tx);
    }

    public function test_mixed_sale_inventory_commerce_manual(): void
    {
        $item = $this->makeItem();
        $this->receipt($item->id, '10', '100');

        $product = $this->makeSimpleProduct('Commerce Sale', 8);

        $sale = Sale::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::Sale),
            'source_type' => 'manual',
            'sale_date' => now()->toDateString(),
            'status' => SaleStatus::Draft->value,
            'branch_id' => $this->branch->id,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Inventory->value,
            'inventory_item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'description_snapshot' => $item->name,
            'quantity' => '3',
            'unit_price' => '150',
            'discount' => 0,
            'tax' => 0,
            'line_total' => 450,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Commerce->value,
            'product_id' => $product->id,
            'description_snapshot' => $product->name,
            'quantity' => '2',
            'unit_price' => '50',
            'discount' => 0,
            'tax' => 0,
            'line_total' => 100,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Manual->value,
            'description_snapshot' => 'Service fee',
            'quantity' => '1',
            'unit_price' => '30',
            'unit_cost' => '0',
            'discount' => 0,
            'tax' => 0,
            'line_total' => 30,
        ]);

        $confirmed = app(ConfirmSaleAction::class)->execute($sale);
        app(ConfirmSaleAction::class)->execute($confirmed); // idempotent

        $this->assertSame(SaleStatus::Confirmed, $confirmed->status);
        $this->assertSame('7.0000', (string) StockBalance::query()
            ->where('inventory_item_id', $item->id)->value('quantity_on_hand'));
        $this->assertSame(6, $product->fresh()->quantity);
        $this->assertSame('300.0000', (string) $confirmed->items()->where('source_type', 'inventory')->value('cost_total'));
        $this->assertSame(1, CommerceQuantityAdjustment::query()->where('reason', 'sale_confirm')->count());
    }

    public function test_inventory_sale_does_not_change_store_quantity(): void
    {
        $item = $this->makeItem();
        $product = $this->makeSimpleProduct('Linked', 5);
        InventoryItemCommerceLink::query()->create([
            'inventory_item_id' => $item->id,
            'source_type' => 'product',
            'source_id' => $product->id,
        ]);
        $this->receipt($item->id, '4', '40');

        $sale = Sale::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::Sale),
            'source_type' => 'manual',
            'sale_date' => now()->toDateString(),
            'status' => SaleStatus::Draft->value,
        ]);

        SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Inventory->value,
            'inventory_item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'description_snapshot' => $item->name,
            'quantity' => '2',
            'unit_price' => '80',
            'line_total' => 160,
        ]);

        app(ConfirmSaleAction::class)->execute($sale);

        $this->assertSame(5, $product->fresh()->quantity);
        $this->assertSame('2.0000', (string) StockBalance::query()->value('quantity_on_hand'));
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
