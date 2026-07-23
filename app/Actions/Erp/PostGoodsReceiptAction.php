<?php

namespace App\Actions\Erp;

use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\PurchaseLineType;
use App\Enums\Erp\PurchaseOrderStatus;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\CommerceQuantityAdjustment;
use App\Models\Tenant\GoodsReceipt;
use App\Models\Tenant\GoodsReceiptItem;
use App\Models\Tenant\PurchaseOrder;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Services\Erp\DocumentNumberService;
use App\Services\Erp\InventoryItemResolver;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ترحيل استلام مشتريات: يزيد ERP (+ المتجر إن كان Commerce) وينشئ طبقات FIFO.
 * أمر الشراء والفاتورة لا يزيدان المخزون.
 */
final class PostGoodsReceiptAction
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly InventoryItemResolver $items,
        private readonly PostStockTransactionAction $postStock,
    ) {}

    public function execute(GoodsReceipt $receipt): GoodsReceipt
    {
        return DB::connection('tenant')->transaction(function () use ($receipt) {
            /** @var GoodsReceipt $gr */
            $gr = GoodsReceipt::query()->whereKey($receipt->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($gr->status === DocumentStatus::Posted) {
                return $gr;
            }

            if ($gr->status !== DocumentStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.only_draft_can_post'),
                ]);
            }

            if ($gr->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => __('erp.validation.lines_required'),
                ]);
            }

            foreach ($gr->items as $item) {
                $this->assertPoRemaining($item);
            }

            $tx = StockTransaction::query()->create([
                'document_number' => $this->numbers->next(DocumentSequenceType::GoodsReceipt),
                'transaction_type' => StockTransactionType::PurchaseReceipt->value,
                'status' => DocumentStatus::Draft->value,
                'branch_id' => $gr->branch_id,
                'source_warehouse_id' => null,
                'destination_warehouse_id' => $gr->warehouse_id,
                'transaction_date' => $gr->receipt_date,
                'reference_type' => $gr->getMorphClass(),
                'reference_id' => $gr->id,
                'notes' => 'Goods receipt '.$gr->document_number,
                'created_by' => Auth::guard('tenant')->id(),
            ]);

            foreach ($gr->items as $item) {
                $lineType = $item->line_type instanceof PurchaseLineType
                    ? $item->line_type
                    : PurchaseLineType::from($item->line_type);

                if (! $lineType->affectsStock()) {
                    continue;
                }

                $inventoryItemId = $item->inventory_item_id;
                $sourceKind = StockLineSourceKind::Inventory;
                $affectsCommerce = false;

                if ($lineType === PurchaseLineType::Commerce) {
                    $resolved = $this->items->resolveOrCreateFromCommerce(
                        $item->product_id,
                        $item->product_variant_id,
                    );
                    $inventoryItemId = $resolved->id;
                    $sourceKind = StockLineSourceKind::Commerce;
                    $affectsCommerce = true;
                    $item->inventory_item_id = $inventoryItemId;
                    $item->save();
                }

                if (! $inventoryItemId) {
                    throw ValidationException::withMessages([
                        'inventory_item_id' => __('erp.validation.inventory_item_required'),
                    ]);
                }

                $stockLine = StockTransactionLine::query()->create([
                    'stock_transaction_id' => $tx->id,
                    'inventory_item_id' => $inventoryItemId,
                    'source_kind' => $sourceKind->value,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'affects_commerce_quantity' => $affectsCommerce,
                    'commerce_quantity_delta' => $affectsCommerce ? $item->quantity : null,
                ]);

                $item->stock_transaction_line_id = $stockLine->id;
                $item->save();
            }

            $posted = $this->postStock->execute($tx);

            foreach ($gr->items as $item) {
                if ($item->stock_transaction_line_id && ($item->product_id || $item->product_variant_id)) {
                    $adj = CommerceQuantityAdjustment::query()
                        ->where('reference_type', $posted->getMorphClass())
                        ->where('reference_id', $posted->id)
                        ->where(function ($q) use ($item) {
                            if ($item->product_variant_id) {
                                $q->where('product_variant_id', $item->product_variant_id);
                            } else {
                                $q->where('product_id', $item->product_id)->whereNull('product_variant_id');
                            }
                        })
                        ->latest('id')
                        ->first();
                    if ($adj) {
                        $item->commerce_quantity_adjustment_id = $adj->id;
                        $item->save();
                    }
                }

                if ($item->purchase_order_item_id) {
                    $poItem = PurchaseOrderItem::query()->whereKey($item->purchase_order_item_id)->lockForUpdate()->first();
                    if ($poItem) {
                        $poItem->received_quantity = Decimal::add($poItem->received_quantity, $item->quantity);
                        $poItem->save();
                    }
                }
            }

            if ($gr->purchase_order_id) {
                $this->refreshPurchaseOrderStatus($gr->purchase_order_id);
            }

            $gr->stock_transaction_id = $posted->id;
            $gr->status = DocumentStatus::Posted;
            $gr->posted_at = now();
            $gr->posted_by = Auth::guard('tenant')->id();
            $gr->save();

            return $gr->fresh('items');
        });
    }

    private function assertPoRemaining(GoodsReceiptItem $item): void
    {
        if (! $item->purchase_order_item_id) {
            return;
        }

        $poItem = PurchaseOrderItem::query()->whereKey($item->purchase_order_item_id)->lockForUpdate()->firstOrFail();
        $remaining = Decimal::sub(
            Decimal::sub($poItem->quantity, $poItem->received_quantity),
            '0'
        );

        if (Decimal::cmp($item->quantity, $remaining) > 0) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.cannot_receive_more_than_remaining', [
                    'remaining' => $remaining,
                ]),
            ]);
        }
    }

    private function refreshPurchaseOrderStatus(int $purchaseOrderId): void
    {
        $po = PurchaseOrder::query()->with('items')->findOrFail($purchaseOrderId);
        $allReceived = true;
        $anyReceived = false;

        foreach ($po->items as $item) {
            if (Decimal::isPositive($item->received_quantity)) {
                $anyReceived = true;
            }
            if (Decimal::cmp($item->received_quantity, $item->quantity) < 0) {
                $allReceived = false;
            }
        }

        if ($allReceived && $anyReceived) {
            $po->status = PurchaseOrderStatus::Received;
        } elseif ($anyReceived) {
            $po->status = PurchaseOrderStatus::PartiallyReceived;
        }

        $po->save();
    }
}
