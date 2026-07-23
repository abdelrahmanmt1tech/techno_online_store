<?php

namespace App\Actions\Erp;

use App\Enums\Erp\CommerceSourceType;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\MovementDirection;
use App\Enums\Erp\ReturnDisposition;
use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Erp\StockTransactionType;
use App\Enums\Erp\WarehouseType;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\SalesReturn;
use App\Models\Tenant\StockLayerConsumption;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Models\Tenant\Warehouse;
use App\Services\Erp\CommerceQuantityService;
use App\Services\Erp\DocumentNumberService;
use App\Services\Erp\FifoCostingService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class PostSalesReturnAction
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly FifoCostingService $fifo,
        private readonly CommerceQuantityService $commerce,
    ) {}

    public function execute(SalesReturn $salesReturn): SalesReturn
    {
        return DB::connection('tenant')->transaction(function () use ($salesReturn) {
            $ret = SalesReturn::query()->whereKey($salesReturn->id)->lockForUpdate()->with('items')->firstOrFail();

            if ($ret->status === DocumentStatus::Posted) {
                return $ret;
            }

            if ($ret->status !== DocumentStatus::Draft) {
                throw ValidationException::withMessages(['status' => __('erp.validation.only_draft_can_post')]);
            }

            if (! filled($ret->reason)) {
                throw ValidationException::withMessages(['reason' => __('erp.validation.return_reason_required')]);
            }

            $sale = Sale::query()->whereKey($ret->sale_id)->lockForUpdate()->firstOrFail();

            $tx = StockTransaction::query()->create([
                'document_number' => $this->numbers->next(DocumentSequenceType::SalesReturn),
                'transaction_type' => StockTransactionType::SaleReturn->value,
                'status' => DocumentStatus::Draft->value,
                'branch_id' => $ret->branch_id,
                'destination_warehouse_id' => $ret->items->first()?->warehouse_id,
                'transaction_date' => $ret->return_date,
                'reference_type' => $ret->getMorphClass(),
                'reference_id' => $ret->id,
                'created_by' => Auth::guard('tenant')->id(),
            ]);

            foreach ($ret->items as $item) {
                /** @var SaleItem $saleItem */
                $saleItem = SaleItem::query()->whereKey($item->sale_item_id)->lockForUpdate()->firstOrFail();
                $remaining = Decimal::sub($saleItem->quantity, $saleItem->returned_quantity);
                if (Decimal::cmp($item->quantity, $remaining) > 0) {
                    throw ValidationException::withMessages([
                        'quantity' => __('erp.validation.cannot_return_more_than_sold', ['remaining' => $remaining]),
                    ]);
                }

                $source = $item->source_type instanceof SaleItemSourceType
                    ? $item->source_type
                    : SaleItemSourceType::from($item->source_type);

                $disposition = $item->disposition instanceof ReturnDisposition
                    ? $item->disposition
                    : ReturnDisposition::from($item->disposition);

                match ($source) {
                    SaleItemSourceType::Inventory => $this->returnInventory($ret, $item, $saleItem, $tx, $disposition),
                    SaleItemSourceType::Commerce => $this->returnCommerce($ret, $item, $saleItem, $disposition),
                    SaleItemSourceType::Manual => null,
                };

                $saleItem->returned_quantity = Decimal::add($saleItem->returned_quantity, $item->quantity);
                $saleItem->save();
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

            $this->refreshSaleStatus($sale);

            return $ret->fresh('items');
        });
    }

    private function returnInventory($ret, $item, SaleItem $saleItem, StockTransaction $tx, ReturnDisposition $disposition): void
    {
        if ($disposition === ReturnDisposition::NotReceived || $disposition === ReturnDisposition::Other) {
            return;
        }

        $warehouseId = $item->warehouse_id;
        if ($disposition === ReturnDisposition::Damaged) {
            // إن لم يُحدد مخزن تالف، نسجّل دخولًا في المخزن المحدد مع ملاحظة — الواجهة تفضّل مخزن damaged
            if (! $warehouseId) {
                throw ValidationException::withMessages(['warehouse_id' => __('erp.validation.warehouse_required')]);
            }
        }

        if (! $warehouseId) {
            throw ValidationException::withMessages(['warehouse_id' => __('erp.validation.warehouse_required')]);
        }

        $stockLine = StockTransactionLine::query()->create([
            'stock_transaction_id' => $tx->id,
            'inventory_item_id' => $saleItem->inventory_item_id,
            'source_kind' => 'inventory',
            'quantity' => $item->quantity,
            'unit_cost' => $saleItem->unit_cost,
            'affects_commerce_quantity' => false,
        ]);

        $in = $this->fifo->makeMovement(
            $tx->id,
            $stockLine->id,
            $warehouseId,
            $saleItem->inventory_item_id,
            MovementDirection::In,
            $item->quantity,
            $saleItem->unit_cost,
            $ret->return_date,
        );

        // إعادة طبقات حسب استهلاك البيع الأصلي إن وُجد
        $originalLineId = $saleItem->stock_transaction_line_id;
        $consumptions = collect();
        if ($originalLineId) {
            $outMovement = StockMovement::query()
                ->where('stock_transaction_line_id', $originalLineId)
                ->where('direction', MovementDirection::Out->value)
                ->first();
            if ($outMovement) {
                $consumptions = StockLayerConsumption::query()
                    ->where('stock_movement_id', $outMovement->id)
                    ->orderBy('id')
                    ->get();
            }
        }

        $remainingToRestore = Decimal::of($item->quantity);
        $costTotal = '0';

        if ($consumptions->isNotEmpty()) {
            foreach ($consumptions as $consumption) {
                if (! Decimal::isPositive($remainingToRestore)) {
                    break;
                }
                $take = Decimal::min($remainingToRestore, $consumption->quantity);
                $this->fifo->createLayerWithQuantity(
                    $in,
                    $take,
                    $consumption->unit_cost,
                    $tx->getMorphClass(),
                    $tx->id,
                );
                $costTotal = Decimal::add($costTotal, Decimal::mul($take, $consumption->unit_cost));
                $remainingToRestore = Decimal::sub($remainingToRestore, $take);
            }
        }

        if (Decimal::isPositive($remainingToRestore)) {
            $this->fifo->createLayerWithQuantity(
                $in,
                $remainingToRestore,
                $saleItem->unit_cost,
                $tx->getMorphClass(),
                $tx->id,
            );
            $costTotal = Decimal::add($costTotal, Decimal::mul($remainingToRestore, $saleItem->unit_cost));
        }

        if ($disposition === ReturnDisposition::Restock) {
            $this->fifo->increaseBalance($warehouseId, $saleItem->inventory_item_id, $item->quantity);
        } else {
            // damaged: زد الرصيد فقط إن كان المخزن من نوع damaged (مخزون غير صالح للبيع العادي)
            $warehouse = Warehouse::query()->findOrFail($warehouseId);
            if ($warehouse->warehouse_type !== WarehouseType::Damaged && $warehouse->warehouse_type !== WarehouseType::Damaged->value) {
                // سجّل الطبقة دون زيادة رصيد المخزن العادي — تكلفة خسارة ظاهرة في الحركة فقط
            } else {
                $this->fifo->increaseBalance($warehouseId, $saleItem->inventory_item_id, $item->quantity);
            }
        }

        $item->stock_transaction_line_id = $stockLine->id;
        $item->cost_total = $costTotal;
        $item->save();
    }

    private function returnCommerce($ret, $item, SaleItem $saleItem, ReturnDisposition $disposition): void
    {
        if ($disposition !== ReturnDisposition::Restock) {
            return;
        }

        $qty = $this->commerce->assertIntegerQuantity($item->quantity);
        $sourceType = $saleItem->product_variant_id
            ? CommerceSourceType::ProductVariant
            : CommerceSourceType::Product;
        $sourceId = $saleItem->product_variant_id ?: $saleItem->product_id;

        $adj = $this->commerce->adjust(
            $sourceType,
            (int) $sourceId,
            $qty,
            'sales_return',
            sprintf('sales-return:%d:item:%d', $ret->id, $item->id),
            'sales_return',
            $ret->document_number,
            $ret->getMorphClass(),
            $ret->id,
        );

        $item->commerce_quantity_adjustment_id = $adj->id;
        $item->save();
    }

    private function refreshSaleStatus(Sale $sale): void
    {
        $sale->load('items');
        $all = true;
        $any = false;
        foreach ($sale->items as $item) {
            if (Decimal::isPositive($item->returned_quantity)) {
                $any = true;
            }
            if (Decimal::cmp($item->returned_quantity, $item->quantity) < 0) {
                $all = false;
            }
        }

        if ($all && $any) {
            $sale->status = SaleStatus::Returned;
        } elseif ($any) {
            $sale->status = SaleStatus::PartiallyReturned;
        }
        $sale->save();
    }
}
