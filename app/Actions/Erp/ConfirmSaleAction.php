<?php

namespace App\Actions\Erp;

use App\Enums\Erp\CommerceSourceType;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\MovementDirection;
use App\Enums\Erp\SaleItemSourceType;
use App\Enums\Erp\SaleStatus;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Models\Tenant\Sale;
use App\Models\Tenant\SaleItem;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Services\Erp\CommerceQuantityService;
use App\Services\Erp\DocumentNumberService;
use App\Services\Erp\FifoCostingService;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * تأكيد المبيعة المختلطة داخل Transaction واحدة.
 * Inventory → FIFO من ERP فقط؛ Commerce → كمية المتجر فقط؛ Manual → بلا مخزون.
 */
final class ConfirmSaleAction
{
    public function __construct(
        private readonly FifoCostingService $fifo,
        private readonly CommerceQuantityService $commerce,
        private readonly DocumentNumberService $numbers,
        private readonly PostStockTransactionAction $postStock,
    ) {}

    public function execute(Sale $sale): Sale
    {
        return DB::connection('tenant')->transaction(function () use ($sale) {
            /** @var Sale $locked */
            $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->with('items')->firstOrFail();

            if (in_array($locked->status, [SaleStatus::Confirmed, SaleStatus::PartiallyInvoiced, SaleStatus::Invoiced, SaleStatus::PartiallyReturned, SaleStatus::Returned], true)) {
                return $locked;
            }

            if ($locked->status !== SaleStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.only_draft_sale_can_confirm'),
                ]);
            }

            if ($locked->order_id) {
                $exists = Sale::query()
                    ->where('order_id', $locked->order_id)
                    ->where('id', '!=', $locked->id)
                    ->whereNotIn('status', [SaleStatus::Cancelled->value, SaleStatus::Reversed->value])
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'order_id' => __('erp.validation.order_already_has_active_sale'),
                    ]);
                }
            }

            if ($locked->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => __('erp.validation.lines_required'),
                ]);
            }

            $this->recalculateTotals($locked);

            $inventoryItems = $locked->items->filter(
                fn (SaleItem $i) => $i->source_type === SaleItemSourceType::Inventory
                    || $i->source_type === SaleItemSourceType::Inventory->value
            );

            $stockTx = null;
            if ($inventoryItems->isNotEmpty()) {
                $stockTx = $this->buildAndPostStockIssue($locked, $inventoryItems);
                $locked->stock_transaction_id = $stockTx->id;
            }

            foreach ($locked->items as $item) {
                $source = $item->source_type instanceof SaleItemSourceType
                    ? $item->source_type
                    : SaleItemSourceType::from($item->source_type);

                match ($source) {
                    SaleItemSourceType::Inventory => $this->applyInventoryCosts($item, $stockTx),
                    SaleItemSourceType::Commerce => $this->applyCommerceIssue($locked, $item),
                    SaleItemSourceType::Manual => $this->applyManualCosts($item),
                };
            }

            $this->recalculateTotals($locked);

            $locked->status = SaleStatus::Confirmed;
            $locked->confirmed_at = now();
            $locked->confirmed_by = Auth::guard('tenant')->id();
            $locked->save();

            return $locked->fresh('items');
        });
    }

    private function buildAndPostStockIssue(Sale $sale, $inventoryItems): StockTransaction
    {
        // كل سطر مخزني له مخزنه — ننشئ مستندًا واحدًا لكل مخزن أو مستندًا واحدًا بخطوط متعددة
        // هنا: مستند واحد؛ المصدر يُحدَّد لكل خط عبر warehouse على السطر (نضع source_warehouse على أول مخزن ونمرّر الوجهة null)
        $firstWarehouse = $inventoryItems->first()->warehouse_id;
        if (! $firstWarehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => __('erp.validation.warehouse_required'),
            ]);
        }

        $tx = StockTransaction::query()->create([
            'document_number' => $this->numbers->next(DocumentSequenceType::StockIssue),
            'transaction_type' => StockTransactionType::SaleIssue->value,
            'status' => DocumentStatus::Draft->value,
            'branch_id' => $sale->branch_id,
            'source_warehouse_id' => $firstWarehouse,
            'destination_warehouse_id' => null,
            'transaction_date' => $sale->sale_date,
            'reference_type' => $sale->getMorphClass(),
            'reference_id' => $sale->id,
            'notes' => 'Sale '.$sale->document_number,
            'created_by' => Auth::guard('tenant')->id(),
        ]);

        foreach ($inventoryItems as $item) {
            if (! $item->warehouse_id || ! $item->inventory_item_id) {
                throw ValidationException::withMessages([
                    'items' => __('erp.validation.inventory_sale_line_incomplete'),
                ]);
            }

            // إن اختلف المخزن عن المصدر الافتراضي نُرحّل كل سطر بمخزنه عبر تعديل مؤقت
            StockTransactionLine::query()->create([
                'stock_transaction_id' => $tx->id,
                'inventory_item_id' => $item->inventory_item_id,
                'source_kind' => StockLineSourceKind::Inventory->value,
                'quantity' => $item->quantity,
                'unit_cost' => null,
                'total_cost' => 0,
                'affects_commerce_quantity' => false,
                'notes' => 'sale_item:'.$item->id,
            ]);
        }

        // ترحيل مخصص لكل سطر بمخزنه لأن PostStockTransactionAction يستخدم source_warehouse_id للمستند
        return $this->postMultiWarehouseSaleIssue($tx, $inventoryItems);
    }

    private function postMultiWarehouseSaleIssue(StockTransaction $tx, $inventoryItems): StockTransaction
    {
        return DB::connection('tenant')->transaction(function () use ($tx, $inventoryItems) {
            $tx = StockTransaction::query()->whereKey($tx->id)->lockForUpdate()->with('lines')->firstOrFail();

            foreach ($tx->lines as $index => $line) {
                /** @var SaleItem $saleItem */
                $saleItem = $inventoryItems->values()->get($index);
                $warehouseId = $saleItem->warehouse_id;

                $movement = $this->fifo->makeMovement(
                    $tx->id,
                    $line->id,
                    $warehouseId,
                    $line->inventory_item_id,
                    MovementDirection::Out,
                    $line->quantity,
                    '0',
                    $tx->transaction_date->startOfDay(),
                );

                $result = $this->fifo->consume($movement, $line->quantity);
                $avg = Decimal::div($result['total_cost'], $line->quantity);
                $movement->unit_cost = $avg;
                $movement->total_cost = $result['total_cost'];
                $movement->save();

                $this->fifo->decreaseBalance($warehouseId, $line->inventory_item_id, $line->quantity);

                $line->unit_cost = $avg;
                $line->total_cost = $result['total_cost'];
                $line->save();

                $saleItem->stock_transaction_line_id = $line->id;
                $saleItem->unit_cost = $avg;
                $saleItem->cost_total = $result['total_cost'];
                $saleItem->profit_total = Decimal::money(Decimal::sub($saleItem->line_total, Decimal::money($result['total_cost'])));
                $saleItem->save();
            }

            $tx->status = DocumentStatus::Posted;
            $tx->posted_at = now();
            $tx->posted_by = Auth::guard('tenant')->id();
            $tx->save();

            return $tx;
        });
    }

    private function applyInventoryCosts(SaleItem $item, ?StockTransaction $stockTx): void
    {
        // التكاليف تُحدَّث أثناء postMultiWarehouseSaleIssue
    }

    private function applyCommerceIssue(Sale $sale, SaleItem $item): void
    {
        $qty = $this->commerce->assertIntegerQuantity($item->quantity);

        if ($item->product_variant_id) {
            $sourceType = CommerceSourceType::ProductVariant;
            $sourceId = (int) $item->product_variant_id;
            $variant = ProductVariant::query()->findOrFail($sourceId);
            $unitCost = Decimal::of($variant->expense ?? '0');
        } elseif ($item->product_id) {
            $sourceType = CommerceSourceType::Product;
            $sourceId = (int) $item->product_id;
            $product = Product::query()->findOrFail($sourceId);

            if ($product->variants()->exists()) {
                throw ValidationException::withMessages([
                    'product_id' => __('erp.validation.product_has_variants_use_variant'),
                ]);
            }

            if ($product->track_stock && $product->disable_orders_for_no_stock && (int) $product->quantity < $qty) {
                throw ValidationException::withMessages([
                    'quantity' => __('erp.validation.insufficient_commerce_quantity', [
                        'available' => $product->quantity,
                        'requested' => $qty,
                    ]),
                ]);
            }

            $unitCost = Decimal::of($product->expense ?? '0');
        } else {
            throw ValidationException::withMessages([
                'commerce' => __('erp.validation.commerce_source_required'),
            ]);
        }

        $adjustment = $this->commerce->adjust(
            $sourceType,
            $sourceId,
            -$qty,
            'sale_confirm',
            sprintf('sale:%d:item:%d:commerce', $sale->id, $item->id),
            'sale',
            $sale->document_number,
            $sale->getMorphClass(),
            $sale->id,
        );

        $item->unit_cost = $unitCost;
        $item->cost_total = Decimal::mul((string) $qty, $unitCost);
        $item->profit_total = Decimal::money(Decimal::sub($item->line_total, Decimal::money($item->cost_total)));
        $item->commerce_quantity_adjustment_id = $adjustment->id;
        $item->save();
    }

    private function applyManualCosts(SaleItem $item): void
    {
        $cost = Decimal::mul($item->quantity, $item->unit_cost ?? '0');
        $item->cost_total = $cost;
        $item->profit_total = Decimal::money(Decimal::sub($item->line_total, Decimal::money($cost)));
        $item->save();
    }

    public function recalculateTotals(Sale $sale): void
    {
        $subtotal = '0';
        $discount = '0';
        $tax = '0';
        $cost = '0';

        foreach ($sale->items as $item) {
            $lineBase = Decimal::mul($item->quantity, $item->unit_price);
            $lineTotal = Decimal::money(Decimal::add(Decimal::sub($lineBase, $item->discount ?? '0'), $item->tax ?? '0'));
            $item->line_total = $lineTotal;
            $item->save();

            $subtotal = Decimal::add($subtotal, $lineBase, 2);
            $discount = Decimal::add($discount, $item->discount ?? '0', 2);
            $tax = Decimal::add($tax, $item->tax ?? '0', 2);
            $cost = Decimal::add($cost, $item->cost_total ?? '0');
        }

        $sale->subtotal = Decimal::money($subtotal);
        $sale->discount_total = Decimal::money($discount);
        $sale->tax_total = Decimal::money($tax);
        $sale->grand_total = Decimal::money(Decimal::add(Decimal::sub($subtotal, $discount, 2), $tax, 2));
        $sale->cost_total = $cost;
        $sale->profit_total = Decimal::money(Decimal::sub($sale->grand_total, Decimal::money($cost)));
        $sale->save();
    }
}
