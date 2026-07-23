<?php

namespace App\Actions\Erp;

use App\Enums\Erp\CommerceSourceType;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\MovementDirection;
use App\Enums\Erp\PurchaseLineType;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\GoodsReceipt;
use App\Models\Tenant\GoodsReceiptItem;
use App\Models\Tenant\PurchaseOrderItem;
use App\Models\Tenant\PurchaseReturn;
use App\Models\Tenant\PurchaseReturnItem;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Services\Erp\CommerceQuantityService;
use App\Services\Erp\DocumentNumberService;
use App\Services\Erp\FifoCostingService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PostPurchaseReturnAction
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly FifoCostingService $fifo,
        private readonly CommerceQuantityService $commerce,
    ) {}

    public function execute(PurchaseReturn $purchaseReturn): PurchaseReturn
    {
        return DB::connection('tenant')->transaction(function () use ($purchaseReturn) {
            $ret = PurchaseReturn::query()->whereKey($purchaseReturn->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($ret->status === DocumentStatus::Posted) {
                return $ret;
            }

            if ($ret->status !== DocumentStatus::Draft) {
                throw ValidationException::withMessages(['status' => __('erp.validation.only_draft_can_post')]);
            }

            $tx = StockTransaction::query()->create([
                'document_number' => $this->numbers->next(DocumentSequenceType::PurchaseReturn),
                'transaction_type' => StockTransactionType::PurchaseReturn->value,
                'status' => DocumentStatus::Draft->value,
                'branch_id' => $ret->branch_id,
                'source_warehouse_id' => $ret->warehouse_id,
                'transaction_date' => $ret->return_date,
                'reference_type' => $ret->getMorphClass(),
                'reference_id' => $ret->id,
                'created_by' => Auth::guard('tenant')->id(),
            ]);

            foreach ($ret->items as $item) {
                $this->assertReturnable($item);

                if (! $item->inventory_item_id) {
                    continue;
                }

                $lineType = $item->line_type instanceof PurchaseLineType
                    ? $item->line_type
                    : PurchaseLineType::from($item->line_type);

                $stockLine = StockTransactionLine::query()->create([
                    'stock_transaction_id' => $tx->id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'source_kind' => $lineType === PurchaseLineType::Commerce
                        ? StockLineSourceKind::Commerce->value
                        : StockLineSourceKind::Inventory->value,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'affects_commerce_quantity' => $lineType === PurchaseLineType::Commerce,
                ]);

                $movement = $this->fifo->makeMovement(
                    $tx->id,
                    $stockLine->id,
                    $ret->warehouse_id,
                    $item->inventory_item_id,
                    MovementDirection::Out,
                    $item->quantity,
                    '0',
                    $ret->return_date,
                );

                if ($item->goods_receipt_item_id && $ret->goods_receipt_id) {
                    $result = $this->fifo->consumeFromSource(
                        $movement,
                        $item->quantity,
                        (new GoodsReceipt)->getMorphClass(),
                        (int) $ret->goods_receipt_id,
                    );
                } else {
                    $result = $this->fifo->consume($movement, $item->quantity);
                }

                $movement->total_cost = $result['total_cost'];
                $movement->unit_cost = Decimal::div($result['total_cost'], $item->quantity);
                $movement->save();

                $this->fifo->decreaseBalance($ret->warehouse_id, $item->inventory_item_id, $item->quantity);

                $item->stock_transaction_line_id = $stockLine->id;
                $item->total_cost = $result['total_cost'];
                $item->save();

                if ($lineType === PurchaseLineType::Commerce) {
                    $qty = $this->commerce->assertIntegerQuantity($item->quantity);
                    $sourceType = $item->product_variant_id
                        ? CommerceSourceType::ProductVariant
                        : CommerceSourceType::Product;
                    $sourceId = $item->product_variant_id ?: $item->product_id;
                    $adj = $this->commerce->adjust(
                        $sourceType,
                        (int) $sourceId,
                        -$qty,
                        'purchase_return',
                        sprintf('purchase-return:%d:item:%d', $ret->id, $item->id),
                        'purchase_return',
                        $ret->document_number,
                        $ret->getMorphClass(),
                        $ret->id,
                    );
                    $item->commerce_quantity_adjustment_id = $adj->id;
                    $item->save();
                }

                if ($item->purchase_order_item_id) {
                    $poItem = PurchaseOrderItem::query()->whereKey($item->purchase_order_item_id)->lockForUpdate()->first();
                    if ($poItem) {
                        $poItem->returned_quantity = Decimal::add($poItem->returned_quantity, $item->quantity);
                        $poItem->save();
                    }
                }
            }

            $tx->status = DocumentStatus::Posted;
            $tx->posted_at = now();
            $tx->posted_by = Auth::guard('tenant')->id();
            $tx->save();

            $ret->stock_transaction_id = $tx->id;
            $ret->status = DocumentStatus::Posted;
            $ret->posted_at = now();
            $ret->posted_by = Auth::guard('tenant')->id();
            $ret->save();

            return $ret->fresh('items');
        });
    }

    private function assertReturnable($item): void
    {
        if (! $item->goods_receipt_item_id) {
            return;
        }

        $grItem = GoodsReceiptItem::query()->whereKey($item->goods_receipt_item_id)->firstOrFail();
        $alreadyReturned = PurchaseReturnItem::query()
            ->where('goods_receipt_item_id', $grItem->id)
            ->whereHas('purchaseReturn', fn ($q) => $q->where('status', DocumentStatus::Posted->value))
            ->sum('quantity');

        $remaining = Decimal::sub($grItem->quantity, (string) $alreadyReturned);
        if (Decimal::cmp($item->quantity, $remaining) > 0) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.cannot_return_more_than_received', ['remaining' => $remaining]),
            ]);
        }
    }
}
