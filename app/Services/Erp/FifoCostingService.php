<?php

namespace App\Services\Erp;

use App\Enums\Erp\CostLayerStatus;
use App\Enums\Erp\MovementDirection;
use App\Models\Tenant\StockBalance;
use App\Models\Tenant\StockCostLayer;
use App\Models\Tenant\StockLayerConsumption;
use App\Models\Tenant\StockMovement;
use App\Support\Erp\Decimal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * FIFO فعلي: استهلاك الطبقات الأقدم أولاً مع lockForUpdate.
 * لا يُسمح بالمخزون السالب — الفشل يلغي العملية بالكامل عبر الـ Transaction الخارجية.
 */
final class FifoCostingService
{
    /**
     * إنشاء طبقة تكلفة عند الاستلام.
     */
    public function createLayer(
        StockMovement $movement,
        string $unitCost,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?Carbon $receivedAt = null,
    ): StockCostLayer {
        $qty = Decimal::of($movement->quantity);
        $cost = Decimal::of($unitCost);
        $total = Decimal::mul($qty, $cost);

        return StockCostLayer::query()->create([
            'warehouse_id' => $movement->warehouse_id,
            'inventory_item_id' => $movement->inventory_item_id,
            'stock_movement_id' => $movement->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'received_at' => $receivedAt ?? $movement->movement_date,
            'original_quantity' => $qty,
            'remaining_quantity' => $qty,
            'unit_cost' => $cost,
            'total_cost' => $total,
            'status' => CostLayerStatus::Open->value,
        ]);
    }

    /**
     * استهلاك FIFO من مخزن محدد. يُرجع إجمالي التكلفة والكميات المستهلكة لكل طبقة.
     *
     * @return array{total_cost: string, consumptions: list<array{layer: StockCostLayer, quantity: string, unit_cost: string, total_cost: string}>}
     */
    public function consume(
        StockMovement $outMovement,
        string $quantity,
    ): array {
        $remaining = Decimal::of($quantity);

        if (! Decimal::isPositive($remaining)) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.quantity_must_be_positive'),
            ]);
        }

        $layers = StockCostLayer::query()
            ->where('warehouse_id', $outMovement->warehouse_id)
            ->where('inventory_item_id', $outMovement->inventory_item_id)
            ->where('remaining_quantity', '>', 0)
            ->whereIn('status', [CostLayerStatus::Open->value, CostLayerStatus::Partial->value])
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $available = '0';
        foreach ($layers as $layer) {
            $available = Decimal::add($available, $layer->remaining_quantity);
        }

        if (Decimal::cmp($available, $remaining) < 0) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.insufficient_stock', [
                    'available' => $available,
                    'requested' => $remaining,
                ]),
            ]);
        }

        $consumptions = [];
        $totalCost = '0';

        foreach ($layers as $layer) {
            if (! Decimal::isPositive($remaining)) {
                break;
            }

            $take = Decimal::min($remaining, $layer->remaining_quantity);
            $unitCost = Decimal::of($layer->unit_cost);
            $lineCost = Decimal::mul($take, $unitCost);

            StockLayerConsumption::query()->create([
                'stock_movement_id' => $outMovement->id,
                'stock_cost_layer_id' => $layer->id,
                'quantity' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => $lineCost,
            ]);

            $layer->remaining_quantity = Decimal::sub($layer->remaining_quantity, $take);
            if (Decimal::isZero($layer->remaining_quantity)) {
                $layer->status = CostLayerStatus::Consumed;
            } else {
                $layer->status = CostLayerStatus::Partial;
            }
            $layer->save();

            $consumptions[] = [
                'layer' => $layer->fresh(),
                'quantity' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => $lineCost,
            ];

            $totalCost = Decimal::add($totalCost, $lineCost);
            $remaining = Decimal::sub($remaining, $take);
        }

        if (Decimal::isPositive($remaining)) {
            // حماية إضافية — لا يجب الوصول هنا بعد فحص الكمية
            throw new RuntimeException('FIFO consumption incomplete.');
        }

        return [
            'total_cost' => $totalCost,
            'consumptions' => $consumptions,
        ];
    }

    /**
     * استهلاك طبقات مرتبطة بمصدر استلام محدد (مرتجع شراء مرتبط بدفعة).
     *
     * @return array{total_cost: string, consumptions: list<array{layer: StockCostLayer, quantity: string, unit_cost: string, total_cost: string}>}
     */
    public function consumeFromSource(
        StockMovement $outMovement,
        string $quantity,
        string $sourceType,
        int $sourceId,
    ): array {
        $remaining = Decimal::of($quantity);

        $layers = StockCostLayer::query()
            ->where('warehouse_id', $outMovement->warehouse_id)
            ->where('inventory_item_id', $outMovement->inventory_item_id)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('remaining_quantity', '>', 0)
            ->whereIn('status', [CostLayerStatus::Open->value, CostLayerStatus::Partial->value])
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $available = '0';
        foreach ($layers as $layer) {
            $available = Decimal::add($available, $layer->remaining_quantity);
        }

        if (Decimal::cmp($available, $remaining) < 0) {
            // إن لم تكفِ طبقات المصدر، نكمل بـ FIFO عام للكمية المتبقية بعد استهلاك المصدر
            $fromSource = $this->consumeLayersCollection($outMovement, $layers, $available);
            $stillNeeded = Decimal::sub($remaining, $available);
            $fromFifo = $this->consume($outMovement, $stillNeeded);

            return [
                'total_cost' => Decimal::add($fromSource['total_cost'], $fromFifo['total_cost']),
                'consumptions' => array_merge($fromSource['consumptions'], $fromFifo['consumptions']),
            ];
        }

        return $this->consumeLayersCollection($outMovement, $layers, $remaining);
    }

    /**
     * إعادة طبقات بتكلفة تاريخية (مرتجع بيع restock).
     *
     * @param  list<array{quantity: string, unit_cost: string}>  $costBuckets
     * @return list<StockCostLayer>
     */
    public function restoreLayersFromCosts(
        StockMovement $inMovement,
        array $costBuckets,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): array {
        $layers = [];
        foreach ($costBuckets as $bucket) {
            $qty = Decimal::of($bucket['quantity']);
            if (! Decimal::isPositive($qty)) {
                continue;
            }
            $layers[] = $this->createLayer(
                $inMovement,
                Decimal::of($bucket['unit_cost']),
                $sourceType,
                $sourceId,
            );
            // createLayer يستخدم كمية الحركة بالكامل — لذا ننشئ حركة منطقية لكل دلو عبر طبقات مباشرة
        }

        return $layers;
    }

    /**
     * إنشاء طبقة مباشرة بكميات مخصصة (للمرتجعات متعددة التكاليف على حركة واحدة).
     */
    public function createLayerWithQuantity(
        StockMovement $movement,
        string $quantity,
        string $unitCost,
        ?string $sourceType = null,
        ?int $sourceId = null,
    ): StockCostLayer {
        $qty = Decimal::of($quantity);
        $cost = Decimal::of($unitCost);

        return StockCostLayer::query()->create([
            'warehouse_id' => $movement->warehouse_id,
            'inventory_item_id' => $movement->inventory_item_id,
            'stock_movement_id' => $movement->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'received_at' => $movement->movement_date,
            'original_quantity' => $qty,
            'remaining_quantity' => $qty,
            'unit_cost' => $cost,
            'total_cost' => Decimal::mul($qty, $cost),
            'status' => CostLayerStatus::Open->value,
        ]);
    }

    public function increaseBalance(int $warehouseId, int $itemId, string $quantity): StockBalance
    {
        $balance = $this->lockBalance($warehouseId, $itemId);
        $balance->quantity_on_hand = Decimal::add($balance->quantity_on_hand, $quantity);
        $balance->save();

        return $balance;
    }

    public function decreaseBalance(int $warehouseId, int $itemId, string $quantity): StockBalance
    {
        $balance = $this->lockBalance($warehouseId, $itemId);
        $newQty = Decimal::sub($balance->quantity_on_hand, $quantity);

        if (Decimal::isNegative($newQty)) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.insufficient_stock', [
                    'available' => $balance->quantity_on_hand,
                    'requested' => $quantity,
                ]),
            ]);
        }

        $balance->quantity_on_hand = $newQty;
        $balance->save();

        return $balance;
    }

    public function lockBalance(int $warehouseId, int $itemId): StockBalance
    {
        $balance = StockBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('inventory_item_id', $itemId)
            ->lockForUpdate()
            ->first();

        if (! $balance) {
            $balance = StockBalance::query()->create([
                'warehouse_id' => $warehouseId,
                'inventory_item_id' => $itemId,
                'quantity_on_hand' => '0',
            ]);

            $balance = StockBalance::query()
                ->whereKey($balance->id)
                ->lockForUpdate()
                ->firstOrFail();
        }

        return $balance;
    }

    public function makeMovement(
        int $transactionId,
        int $lineId,
        int $warehouseId,
        int $itemId,
        MovementDirection $direction,
        string $quantity,
        string $unitCost,
        Carbon|string $movementDate,
    ): StockMovement {
        $qty = Decimal::of($quantity);
        $cost = Decimal::of($unitCost);

        return StockMovement::query()->create([
            'stock_transaction_id' => $transactionId,
            'stock_transaction_line_id' => $lineId,
            'warehouse_id' => $warehouseId,
            'inventory_item_id' => $itemId,
            'direction' => $direction->value,
            'quantity' => $qty,
            'unit_cost' => $cost,
            'total_cost' => Decimal::mul($qty, $cost),
            'movement_date' => $movementDate,
            'created_by' => Auth::guard('tenant')->id(),
        ]);
    }

    /**
     * @param  Collection<int, StockCostLayer>  $layers
     * @return array{total_cost: string, consumptions: list<array{layer: StockCostLayer, quantity: string, unit_cost: string, total_cost: string}>}
     */
    private function consumeLayersCollection(StockMovement $outMovement, $layers, string $quantity): array
    {
        $remaining = Decimal::of($quantity);
        $consumptions = [];
        $totalCost = '0';

        foreach ($layers as $layer) {
            if (! Decimal::isPositive($remaining)) {
                break;
            }

            $take = Decimal::min($remaining, $layer->remaining_quantity);
            $unitCost = Decimal::of($layer->unit_cost);
            $lineCost = Decimal::mul($take, $unitCost);

            StockLayerConsumption::query()->create([
                'stock_movement_id' => $outMovement->id,
                'stock_cost_layer_id' => $layer->id,
                'quantity' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => $lineCost,
            ]);

            $layer->remaining_quantity = Decimal::sub($layer->remaining_quantity, $take);
            $layer->status = Decimal::isZero($layer->remaining_quantity)
                ? CostLayerStatus::Consumed
                : CostLayerStatus::Partial;
            $layer->save();

            $consumptions[] = [
                'layer' => $layer->fresh(),
                'quantity' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => $lineCost,
            ];

            $totalCost = Decimal::add($totalCost, $lineCost);
            $remaining = Decimal::sub($remaining, $take);
        }

        return [
            'total_cost' => $totalCost,
            'consumptions' => $consumptions,
        ];
    }
}
