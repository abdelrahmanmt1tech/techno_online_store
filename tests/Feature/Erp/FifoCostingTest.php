<?php

namespace Tests\Feature\Erp;

use App\Actions\Erp\PostStockTransactionAction;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\InventoryItemCommerceLink;
use App\Models\Tenant\StockBalance;
use App\Models\Tenant\StockCostLayer;
use App\Models\Tenant\StockLayerConsumption;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Services\Erp\DocumentNumberService;
use Illuminate\Validation\ValidationException;

class FifoCostingTest extends ErpTestCase
{
    public function test_fifo_receipt_then_issue_consumes_oldest_layers(): void
    {
        $item = $this->makeItem();

        $this->postReceipt($item->id, '10', '100');
        $this->postReceipt($item->id, '5', '120');

        $issue = $this->postIssue($item->id, '12');

        $this->assertSame(DocumentStatus::Posted, $issue->status);
        $line = $issue->lines()->first();
        $this->assertSame('1240.0000', (string) $line->total_cost);

        $layers = StockCostLayer::query()
            ->where('inventory_item_id', $item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->orderBy('id')
            ->get();

        $this->assertSame('0.0000', (string) $layers[0]->remaining_quantity);
        $this->assertSame('3.0000', (string) $layers[1]->remaining_quantity);
        $this->assertSame('120.0000', (string) $layers[1]->unit_cost);

        $balance = StockBalance::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('inventory_item_id', $item->id)
            ->first();

        $this->assertSame('3.0000', (string) $balance->quantity_on_hand);

        $consumptions = StockLayerConsumption::query()->orderBy('id')->get();
        $this->assertCount(2, $consumptions);
        $this->assertSame('10.0000', (string) $consumptions[0]->quantity);
        $this->assertSame('2.0000', (string) $consumptions[1]->quantity);
    }

    public function test_warehouses_have_independent_layers(): void
    {
        $item = $this->makeItem();
        $this->postReceipt($item->id, '10', '100', $this->warehouse->id);
        $this->postReceipt($item->id, '10', '200', $this->warehouseB->id);

        $issue = $this->postIssue($item->id, '5', $this->warehouse->id);
        $this->assertSame('500.0000', (string) $issue->lines()->first()->total_cost);

        $layerB = StockCostLayer::query()
            ->where('warehouse_id', $this->warehouseB->id)
            ->where('inventory_item_id', $item->id)
            ->first();

        $this->assertSame('10.0000', (string) $layerB->remaining_quantity);
    }

    public function test_insufficient_stock_rolls_back_completely(): void
    {
        $item = $this->makeItem();
        $this->postReceipt($item->id, '5', '100');

        try {
            $this->postIssue($item->id, '6');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('quantity', $e->errors());
        }

        $balance = StockBalance::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('inventory_item_id', $item->id)
            ->first();

        $this->assertSame('5.0000', (string) $balance->quantity_on_hand);
        $this->assertSame(0, StockLayerConsumption::query()->count());
    }

    public function test_transfer_preserves_layer_costs_and_does_not_touch_commerce(): void
    {
        $item = $this->makeItem();
        $product = $this->makeSimpleProduct('Linked', 7);

        // ربط الصنف بالمنتج دون مزامنة
        InventoryItemCommerceLink::query()->create([
            'inventory_item_id' => $item->id,
            'source_type' => 'product',
            'source_id' => $product->id,
        ]);

        $this->postReceipt($item->id, '4', '50');
        $this->postReceipt($item->id, '6', '80');

        $tx = StockTransaction::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::StockTransfer),
            'transaction_type' => StockTransactionType::Transfer->value,
            'status' => DocumentStatus::Draft->value,
            'source_warehouse_id' => $this->warehouse->id,
            'destination_warehouse_id' => $this->warehouseB->id,
            'transaction_date' => now()->toDateString(),
        ]);

        StockTransactionLine::query()->create([
            'stock_transaction_id' => $tx->id,
            'inventory_item_id' => $item->id,
            'source_kind' => StockLineSourceKind::Inventory->value,
            'quantity' => '5',
            'unit_cost' => 0,
        ]);

        app(PostStockTransactionAction::class)->execute($tx);

        $this->assertSame('5.0000', (string) StockBalance::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('inventory_item_id', $item->id)
            ->value('quantity_on_hand'));

        $this->assertSame('5.0000', (string) StockBalance::query()
            ->where('warehouse_id', $this->warehouseB->id)
            ->where('inventory_item_id', $item->id)
            ->value('quantity_on_hand'));

        $destLayers = StockCostLayer::query()
            ->where('warehouse_id', $this->warehouseB->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $destLayers);
        $this->assertSame('50.0000', (string) $destLayers[0]->unit_cost);
        $this->assertSame('80.0000', (string) $destLayers[1]->unit_cost);
        $this->assertSame(7, $product->fresh()->quantity);
    }

    private function postReceipt(int $itemId, string $qty, string $cost, ?int $warehouseId = null): StockTransaction
    {
        $tx = StockTransaction::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::StockReceipt),
            'transaction_type' => StockTransactionType::ManualReceipt->value,
            'status' => DocumentStatus::Draft->value,
            'destination_warehouse_id' => $warehouseId ?? $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
        ]);

        StockTransactionLine::query()->create([
            'stock_transaction_id' => $tx->id,
            'inventory_item_id' => $itemId,
            'source_kind' => StockLineSourceKind::Inventory->value,
            'quantity' => $qty,
            'unit_cost' => $cost,
        ]);

        return app(PostStockTransactionAction::class)->execute($tx);
    }

    private function postIssue(int $itemId, string $qty, ?int $warehouseId = null): StockTransaction
    {
        $tx = StockTransaction::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::StockIssue),
            'transaction_type' => StockTransactionType::ManualIssue->value,
            'status' => DocumentStatus::Draft->value,
            'source_warehouse_id' => $warehouseId ?? $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
        ]);

        StockTransactionLine::query()->create([
            'stock_transaction_id' => $tx->id,
            'inventory_item_id' => $itemId,
            'source_kind' => StockLineSourceKind::Inventory->value,
            'quantity' => $qty,
            'unit_cost' => 0,
        ]);

        return app(PostStockTransactionAction::class)->execute($tx);
    }
}
