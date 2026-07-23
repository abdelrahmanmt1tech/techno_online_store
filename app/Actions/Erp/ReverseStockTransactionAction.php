<?php

namespace App\Actions\Erp;

use App\Enums\Erp\CommerceSourceType;
use App\Enums\Erp\DocumentSequenceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\MovementDirection;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\StockLayerConsumption;
use App\Models\Tenant\StockMovement;
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
 * عكس مستند مُرحّل: مستند عكسي موثّق + عكس طبقات/أرصدة/كمية متجر.
 * لا يُحذف المستند الأصلي.
 */
final class ReverseStockTransactionAction
{
    public function __construct(
        private readonly FifoCostingService $fifo,
        private readonly CommerceQuantityService $commerce,
        private readonly DocumentNumberService $numbers,
    ) {}

    public function execute(StockTransaction $transaction): StockTransaction
    {
        return DB::connection('tenant')->transaction(function () use ($transaction) {
            /** @var StockTransaction $tx */
            $tx = StockTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->with(['lines', 'movements.consumptions'])
                ->firstOrFail();

            if ($tx->status === DocumentStatus::Reversed) {
                return StockTransaction::query()->findOrFail($tx->reversal_transaction_id);
            }

            if ($tx->status !== DocumentStatus::Posted) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.only_posted_can_reverse'),
                ]);
            }

            $reversal = StockTransaction::query()->create([
                'document_number' => $this->numbers->next(DocumentSequenceType::StockAdjustment),
                'transaction_type' => StockTransactionType::Reversal->value,
                'status' => DocumentStatus::Draft->value,
                'branch_id' => $tx->branch_id,
                'source_warehouse_id' => $tx->destination_warehouse_id ?? $tx->source_warehouse_id,
                'destination_warehouse_id' => $tx->source_warehouse_id ?? $tx->destination_warehouse_id,
                'transaction_date' => now()->toDateString(),
                'reference_type' => $tx->getMorphClass(),
                'reference_id' => $tx->id,
                'notes' => 'Reversal of '.$tx->document_number,
                'created_by' => Auth::guard('tenant')->id(),
            ]);

            foreach ($tx->lines as $line) {
                StockTransactionLine::query()->create([
                    'stock_transaction_id' => $reversal->id,
                    'inventory_item_id' => $line->inventory_item_id,
                    'source_kind' => $line->source_kind,
                    'quantity' => $line->quantity,
                    'unit_cost' => $line->unit_cost,
                    'total_cost' => $line->total_cost,
                    'product_id' => $line->product_id,
                    'product_variant_id' => $line->product_variant_id,
                    'affects_commerce_quantity' => false,
                    'notes' => 'Reversal line of #'.$line->id,
                ]);
            }

            // عكس الحركات الفعلية مباشرة للحفاظ على تكاليف الطبقات
            $movements = StockMovement::query()
                ->where('stock_transaction_id', $tx->id)
                ->orderBy('id')
                ->get();

            foreach ($movements as $movement) {
                $this->reverseMovement($reversal, $movement);
            }

            // عكس تأثير كمية المتجر إن وُجد
            foreach ($tx->lines as $line) {
                if (! $line->affects_commerce_quantity || ! $line->commerce_quantity_delta) {
                    continue;
                }

                $originalDelta = (int) $line->commerce_quantity_delta;
                $reverseDelta = -$originalDelta;
                $sourceType = $line->product_variant_id
                    ? CommerceSourceType::ProductVariant
                    : CommerceSourceType::Product;
                $sourceId = $line->product_variant_id ?: $line->product_id;

                if (! $sourceId) {
                    continue;
                }

                $this->commerce->adjust(
                    $sourceType,
                    (int) $sourceId,
                    $reverseDelta,
                    'stock_transaction_reversal',
                    sprintf('stock-tx-reverse:%d:line:%d', $tx->id, $line->id),
                    'stock_transaction',
                    $reversal->document_number,
                    $reversal->getMorphClass(),
                    $reversal->id,
                );
            }

            $reversal->status = DocumentStatus::Posted;
            $reversal->posted_at = now();
            $reversal->posted_by = Auth::guard('tenant')->id();
            $reversal->save();

            $tx->status = DocumentStatus::Reversed;
            $tx->reversed_at = now();
            $tx->reversed_by = Auth::guard('tenant')->id();
            $tx->reversal_transaction_id = $reversal->id;
            $tx->save();

            return $reversal->fresh();
        });
    }

    private function reverseMovement(StockTransaction $reversal, StockMovement $movement): void
    {
        $reversalLine = StockTransactionLine::query()
            ->where('stock_transaction_id', $reversal->id)
            ->where('inventory_item_id', $movement->inventory_item_id)
            ->first();

        if (! $reversalLine) {
            return;
        }

        $direction = $movement->direction instanceof MovementDirection
            ? $movement->direction
            : MovementDirection::from($movement->direction);

        if ($direction === MovementDirection::In) {
            // عكس دخول = صرف من نفس الطبقات التي أنشأها هذا الدخول قدر الإمكان
            $out = $this->fifo->makeMovement(
                $reversal->id,
                $reversalLine->id,
                $movement->warehouse_id,
                $movement->inventory_item_id,
                MovementDirection::Out,
                $movement->quantity,
                $movement->unit_cost,
                now(),
            );

            $layer = $movement->costLayer ?? null;
            if ($layer && Decimal::cmp($layer->remaining_quantity, '0') > 0) {
                $this->fifo->consumeFromSource(
                    $out,
                    Decimal::min($movement->quantity, $layer->remaining_quantity),
                    $layer->source_type ?? $movement->getMorphClass(),
                    (int) ($layer->source_id ?? $movement->stock_transaction_id),
                );
            } else {
                $this->fifo->consume($out, $movement->quantity);
            }

            $this->fifo->decreaseBalance($movement->warehouse_id, $movement->inventory_item_id, $movement->quantity);
        } else {
            // عكس خروج = إعادة طبقات حسب consumptions الأصلية
            $in = $this->fifo->makeMovement(
                $reversal->id,
                $reversalLine->id,
                $movement->warehouse_id,
                $movement->inventory_item_id,
                MovementDirection::In,
                $movement->quantity,
                $movement->unit_cost,
                now(),
            );

            $consumptions = StockLayerConsumption::query()
                ->where('stock_movement_id', $movement->id)
                ->get();

            if ($consumptions->isEmpty()) {
                $this->fifo->createLayerWithQuantity(
                    $in,
                    $movement->quantity,
                    $movement->unit_cost,
                    $reversal->getMorphClass(),
                    $reversal->id,
                );
            } else {
                foreach ($consumptions as $consumption) {
                    $this->fifo->createLayerWithQuantity(
                        $in,
                        $consumption->quantity,
                        $consumption->unit_cost,
                        $reversal->getMorphClass(),
                        $reversal->id,
                    );
                }
            }

            $this->fifo->increaseBalance($movement->warehouse_id, $movement->inventory_item_id, $movement->quantity);
        }
    }
}
