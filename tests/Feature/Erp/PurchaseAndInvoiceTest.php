<?php

namespace Tests\Feature\Erp;

use App\Actions\Erp\ApprovePurchaseOrderAction;
use App\Actions\Erp\ConfirmSaleAction;
use App\Actions\Erp\CreatePurchaseInvoiceAction;
use App\Actions\Erp\CreateSalesInvoiceAction;
use App\Actions\Erp\PostGoodsReceiptAction;
use App\Actions\Erp\PostStockTransactionAction;
use App\Actions\Erp\RecordInvoicePaymentAction;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\InvoicePayableType;
use App\Enums\Erp\InvoiceStatus;
use App\Enums\Erp\PaymentMethod;
use App\Enums\Erp\PurchaseLineType;
use App\Enums\Erp\PurchaseOrderStatus;
use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\GoodsReceipt;
use App\Models\Tenant\GoodsReceiptItem;
use App\Models\Tenant\Order;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\StockBalance;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Models\Tenant\Supplier;
use App\Services\Erp\DocumentNumberService;
use Illuminate\Validation\ValidationException;

class PurchaseAndInvoiceTest extends ErpTestCase
{
    public function test_purchase_order_and_invoice_do_not_increase_stock_until_goods_receipt(): void
    {
        $item = $this->makeItem();
        $supplier = Supplier::query()->create(['name' => 'Supplier A', 'is_active' => true]);

        $po = PurchaseOrder::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::PurchaseOrder),
            'supplier_id' => $supplier->id,
            'target_warehouse_id' => $this->warehouse->id,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrderStatus::Draft->value,
        ]);

        PurchaseOrderItem::query()->create([
            'purchase_order_id' => $po->id,
            'line_type' => PurchaseLineType::Inventory->value,
            'inventory_item_id' => $item->id,
            'description' => $item->name,
            'quantity' => '10',
            'unit_cost' => '40',
            'line_total' => 400,
        ]);

        app(ApprovePurchaseOrderAction::class)->execute($po);
        $this->assertNull(StockBalance::query()->where('inventory_item_id', $item->id)->first());

        $gr = GoodsReceipt::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::GoodsReceipt),
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft->value,
        ]);

        GoodsReceiptItem::query()->create([
            'goods_receipt_id' => $gr->id,
            'purchase_order_item_id' => $po->items()->first()->id,
            'line_type' => PurchaseLineType::Inventory->value,
            'inventory_item_id' => $item->id,
            'description_snapshot' => $item->name,
            'quantity' => '4',
            'unit_cost' => '40',
            'total_cost' => 160,
        ]);

        app(PostGoodsReceiptAction::class)->execute($gr);

        $this->assertSame('4.0000', (string) StockBalance::query()
            ->where('inventory_item_id', $item->id)->value('quantity_on_hand'));
        $this->assertSame(PurchaseOrderStatus::PartiallyReceived, $po->fresh()->status);

        $invoice = app(CreatePurchaseInvoiceAction::class)->execute($gr->fresh());
        $this->assertSame(InvoiceStatus::Issued, $invoice->status);
        $this->assertSame('4.0000', (string) StockBalance::query()
            ->where('inventory_item_id', $item->id)->value('quantity_on_hand'));

        $payment = app(RecordInvoicePaymentAction::class)->execute(
            InvoicePayableType::PurchaseInvoice,
            $invoice->id,
            '100',
            PaymentMethod::Cash,
        );

        $this->assertSame(InvoiceStatus::PartiallyPaid, $invoice->fresh()->status);
        $this->assertSame('100.00', (string) $invoice->fresh()->paid_amount);

        app(RecordInvoicePaymentAction::class)->execute(
            InvoicePayableType::PurchaseInvoice,
            $invoice->id,
            '60',
            PaymentMethod::Cash,
            idempotencyKey: 'pay-full-'.$invoice->id,
        );

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNotNull($payment->document_number);
    }

    public function test_cannot_receive_more_than_po_remaining(): void
    {
        $item = $this->makeItem();
        $supplier = Supplier::query()->create(['name' => 'Supplier B', 'is_active' => true]);
        $po = PurchaseOrder::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::PurchaseOrder),
            'supplier_id' => $supplier->id,
            'order_date' => now()->toDateString(),
            'status' => PurchaseOrderStatus::Approved->value,
        ]);
        $poItem = PurchaseOrderItem::query()->create([
            'purchase_order_id' => $po->id,
            'line_type' => PurchaseLineType::Inventory->value,
            'inventory_item_id' => $item->id,
            'description' => $item->name,
            'quantity' => '3',
            'unit_cost' => '10',
            'line_total' => 30,
        ]);

        $gr = GoodsReceipt::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::GoodsReceipt),
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'warehouse_id' => $this->warehouse->id,
            'receipt_date' => now()->toDateString(),
            'status' => DocumentStatus::Draft->value,
        ]);
        GoodsReceiptItem::query()->create([
            'goods_receipt_id' => $gr->id,
            'purchase_order_item_id' => $poItem->id,
            'line_type' => PurchaseLineType::Inventory->value,
            'inventory_item_id' => $item->id,
            'description_snapshot' => $item->name,
            'quantity' => '5',
            'unit_cost' => '10',
            'total_cost' => 50,
        ]);

        $this->expectException(ValidationException::class);
        app(PostGoodsReceiptAction::class)->execute($gr);
    }

    public function test_sales_invoice_copies_order_id_and_allows_partial(): void
    {
        $item = $this->makeItem();
        $this->receipt($item->id, '10', '20');

        $order = Order::query()->create([
            'order_number' => '#9001',
            'token' => (string) str()->uuid(),
            'customer_name' => 'Buyer',
            'customer_phone' => '0100',
            'customer_address' => 'Test Street',
            'governorate_name' => 'Cairo',
            'status' => 'pending',
            'shipping_cost' => 0,
            'discount' => 0,
            'subtotal' => 100,
            'total' => 100,
        ]);

        $sale = Sale::query()->create([
            'document_number' => app(DocumentNumberService::class)->next(DocumentSequenceType::Sale),
            'source_type' => 'manual',
            'order_id' => $order->id,
            'sale_date' => now()->toDateString(),
            'status' => SaleStatus::Draft->value,
        ]);

        $saleItem = SaleItem::query()->create([
            'sale_id' => $sale->id,
            'source_type' => SaleItemSourceType::Inventory->value,
            'inventory_item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'description_snapshot' => $item->name,
            'quantity' => '4',
            'unit_price' => '50',
            'line_total' => 200,
        ]);

        app(ConfirmSaleAction::class)->execute($sale);

        $inv1 = app(CreateSalesInvoiceAction::class)->execute($sale->fresh(), [
            ['sale_item_id' => $saleItem->id, 'quantity' => '1'],
        ]);
        $this->assertSame($order->id, $inv1->order_id);
        $this->assertSame(SaleStatus::PartiallyInvoiced, $sale->fresh()->status);

        $inv2 = app(CreateSalesInvoiceAction::class)->execute($sale->fresh(), [
            ['sale_item_id' => $saleItem->id, 'quantity' => '3'],
        ]);
        $this->assertSame($order->id, $inv2->order_id);
        $this->assertSame(SaleStatus::Invoiced, $sale->fresh()->status);
        $this->assertNotSame($inv1->id, $inv2->id);

        $this->expectException(ValidationException::class);
        app(CreateSalesInvoiceAction::class)->execute($sale->fresh(), [
            ['sale_item_id' => $saleItem->id, 'quantity' => '1'],
        ]);
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
