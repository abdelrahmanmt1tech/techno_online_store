<?php

namespace App\Actions\Erp;

use App\Enums\Erp\CommerceSourceType;
use App\Enums\Erp\DocumentStatus;
use App\Enums\Erp\InventoryItemType;
use App\Enums\Erp\MovementDirection;
use App\Enums\Erp\StockLineSourceKind;
use App\Enums\Erp\StockTransactionType;
use App\Models\Tenant\InventoryItem;
use App\Models\Tenant\StockTransaction;
use App\Models\Tenant\StockTransactionLine;
use App\Services\Erp\CommerceQuantityService;
use App\Services\Erp\FifoCostingService;
use App\Services\Erp\InventoryItemResolver;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ترحيل مستند مخزون: أرصدة + حركات + طبقات FIFO + تأثير متجر اختياري.
 * لا نستخدم Observer — التأثير يحدث هنا فقط وبصورة صريحة.
 */
final class PostStockTransactionAction
{
    public function __construct(
        private readonly FifoCostingService $fifo,
        private readonly CommerceQuantityService $commerce,
        private readonly InventoryItemResolver $itemResolver,
    ) {}

    public function execute(StockTransaction $transaction): StockTransaction
    {
        return DB::connection('tenant')->transaction(function () use ($transaction) {
            /** @var StockTransaction $tx */
            $tx = StockTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->with('lines')
                ->firstOrFail();

            // Idempotent: إن كان مُرحّلًا مسبقًا أعده كما هو
            if ($tx->status === DocumentStatus::Posted) {
                return $tx;
            }

            if ($tx->status !== DocumentStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => __('erp.validation.only_draft_can_post'),
                ]);
            }

            if ($tx->lines->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => __('erp.validation.lines_required'),
                ]);
            }

            $type = $tx->transaction_type instanceof StockTransactionType
                ? $tx->transaction_type
                : StockTransactionType::from($tx->transaction_type);

            foreach ($tx->lines as $line) {
                $this->postLine($tx, $line, $type);
            }

            $tx->status = DocumentStatus::Posted;
            $tx->posted_at = now();
            $tx->posted_by = Auth::guard('tenant')->id();
            $tx->save();

            return $tx->fresh(['lines', 'movements']);
        });
    }

    private function postLine(StockTransaction $tx, StockTransactionLine $line, StockTransactionType $type): void
    {
        $item = $this->resolveItem($line);

        if (! $item->track_stock || $item->item_type === InventoryItemType::Service) {
            return;
        }

        $qty = Decimal::of($line->quantity);
        if (! Decimal::isPositive($qty)) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.quantity_must_be_positive'),
            ]);
        }

        $kind = $line->source_kind instanceof StockLineSourceKind
            ? $line->source_kind
            : StockLineSourceKind::tryFrom((string) $line->source_kind);

        // تحقق مبكرًا قبل أي تعديل على الأرصدة أو الطبقات
        if ($kind === StockLineSourceKind::Commerce || $line->affects_commerce_quantity) {
            $this->commerce->assertIntegerQuantity($this->commerceQtyOrFail($line, $qty));
        }

        $unitCost = Decimal::of($line->unit_cost ?? '0');

        if ($type->isTransfer()) {
            $this->postTransfer($tx, $line, $item, $qty);

            return;
        }

        if ($type->isInbound() || $type === StockTransactionType::Reversal) {
            // Reversal يُعالَج عادة عبر ReverseStockTransactionAction
            $this->postInbound($tx, $line, $item, $qty, $unitCost);

            return;
        }

        if ($type->isOutbound()) {
            $this->postOutbound($tx, $line, $item, $qty);

            return;
        }

        throw ValidationException::withMessages([
            'transaction_type' => __('erp.validation.unsupported_transaction_type'),
        ]);
    }

    private function postInbound(
        StockTransaction $tx,
        StockTransactionLine $line,
        InventoryItem $item,
        string $qty,
        string $unitCost,
    ): void {
        $warehouseId = $tx->destination_warehouse_id ?? $tx->source_warehouse_id;
        if (! $warehouseId) {
            throw ValidationException::withMessages([
                'warehouse' => __('erp.validation.warehouse_required'),
            ]);
        }

        $movement = $this->fifo->makeMovement(
            $tx->id,
            $line->id,
            $warehouseId,
            $item->id,
            MovementDirection::In,
            $qty,
            $unitCost,
            $tx->transaction_date->startOfDay(),
        );

        $this->fifo->createLayer(
            $movement,
            $unitCost,
            $tx->getMorphClass(),
            $tx->id,
        );

        $this->fifo->increaseBalance($warehouseId, $item->id, $qty);

        $line->total_cost = Decimal::mul($qty, $unitCost);
        $line->inventory_item_id = $item->id;
        $line->save();

        $this->maybeAdjustCommerce($tx, $line, $item, $qty, increase: true);
    }

    private function postOutbound(
        StockTransaction $tx,
        StockTransactionLine $line,
        InventoryItem $item,
        string $qty,
    ): void {
        $warehouseId = $tx->source_warehouse_id;
        if (! $warehouseId) {
            throw ValidationException::withMessages([
                'warehouse' => __('erp.validation.source_warehouse_required'),
            ]);
        }

        $movement = $this->fifo->makeMovement(
            $tx->id,
            $line->id,
            $warehouseId,
            $item->id,
            MovementDirection::Out,
            $qty,
            '0',
            $tx->transaction_date->startOfDay(),
        );

        $result = $this->fifo->consume($movement, $qty);
        $avgUnit = Decimal::isPositive($qty)
            ? Decimal::div($result['total_cost'], $qty)
            : '0';

        $movement->unit_cost = $avgUnit;
        $movement->total_cost = $result['total_cost'];
        $movement->save();

        $this->fifo->decreaseBalance($warehouseId, $item->id, $qty);

        $line->unit_cost = $avgUnit;
        $line->total_cost = $result['total_cost'];
        $line->inventory_item_id = $item->id;
        $line->save();

        // الصرف اليدوي/الإتلاف/التسوية لا يمس كمية المتجر إلا إن طُلب صراحة أو كان السطر Commerce
        $this->maybeAdjustCommerce($tx, $line, $item, $qty, increase: false);
    }

    private function postTransfer(
        StockTransaction $tx,
        StockTransactionLine $line,
        InventoryItem $item,
        string $qty,
    ): void {
        if (! $tx->source_warehouse_id || ! $tx->destination_warehouse_id) {
            throw ValidationException::withMessages([
                'warehouse' => __('erp.validation.transfer_warehouses_required'),
            ]);
        }

        if ($tx->source_warehouse_id === $tx->destination_warehouse_id) {
            throw ValidationException::withMessages([
                'warehouse' => __('erp.validation.transfer_warehouses_must_differ'),
            ]);
        }

        // 1) صرف من المصدر مع استهلاك FIFO
        $out = $this->fifo->makeMovement(
            $tx->id,
            $line->id,
            $tx->source_warehouse_id,
            $item->id,
            MovementDirection::Out,
            $qty,
            '0',
            $tx->transaction_date->startOfDay(),
        );
        $consumed = $this->fifo->consume($out, $qty);
        $out->total_cost = $consumed['total_cost'];
        $out->unit_cost = Decimal::div($consumed['total_cost'], $qty);
        $out->save();
        $this->fifo->decreaseBalance($tx->source_warehouse_id, $item->id, $qty);

        // 2) إدخال للوجهة بطبقات منفصلة بنفس التكاليف الأصلية (ليس متوسطًا)
        foreach ($consumed['consumptions'] as $bucket) {
            $in = $this->fifo->makeMovement(
                $tx->id,
                $line->id,
                $tx->destination_warehouse_id,
                $item->id,
                MovementDirection::In,
                $bucket['quantity'],
                $bucket['unit_cost'],
                $tx->transaction_date->startOfDay(),
            );
            $this->fifo->createLayerWithQuantity(
                $in,
                $bucket['quantity'],
                $bucket['unit_cost'],
                $tx->getMorphClass(),
                $tx->id,
            );
            $this->fifo->increaseBalance($tx->destination_warehouse_id, $item->id, $bucket['quantity']);
        }

        $line->unit_cost = $out->unit_cost;
        $line->total_cost = $consumed['total_cost'];
        $line->inventory_item_id = $item->id;
        $line->save();

        // النقل لا يغيّر كمية المتجر أبدًا
    }

    private function resolveItem(StockTransactionLine $line): InventoryItem
    {
        if ($line->inventory_item_id) {
            return InventoryItem::query()->findOrFail($line->inventory_item_id);
        }

        $kind = $line->source_kind instanceof StockLineSourceKind
            ? $line->source_kind
            : StockLineSourceKind::from($line->source_kind);

        if ($kind === StockLineSourceKind::Commerce) {
            $item = $this->itemResolver->resolveOrCreateFromCommerce(
                $line->product_id,
                $line->product_variant_id,
            );
            $line->inventory_item_id = $item->id;
            $line->save();

            return $item;
        }

        throw ValidationException::withMessages([
            'inventory_item_id' => __('erp.validation.inventory_item_required'),
        ]);
    }

    private function commerceQtyOrFail(StockTransactionLine $line, string $qty): string
    {
        $delta = $line->commerce_quantity_delta !== null
            ? Decimal::of($line->commerce_quantity_delta)
            : $qty;

        // مرتبط بمتجر ⇒ يجب أن تكون الكمية عددًا صحيحًا
        if ($line->product_id || $line->product_variant_id || $line->affects_commerce_quantity) {
            $this->commerce->assertIntegerQuantity($delta);
        }

        return $delta;
    }

    private function maybeAdjustCommerce(
        StockTransaction $tx,
        StockTransactionLine $line,
        InventoryItem $item,
        string $qty,
        bool $increase,
    ): void {
        $kind = $line->source_kind instanceof StockLineSourceKind
            ? $line->source_kind
            : StockLineSourceKind::tryFrom((string) $line->source_kind);

        // الاستلام من Commerce يؤثر دائمًا؛ الصرف يؤثر فقط إن طُلب صراحة
        $shouldAffect = $line->affects_commerce_quantity
            || ($increase && $kind === StockLineSourceKind::Commerce);

        if (! $shouldAffect) {
            return;
        }

        $link = $item->commerceLink;
        $sourceType = null;
        $sourceId = null;

        if ($link) {
            $sourceType = $link->source_type instanceof CommerceSourceType
                ? $link->source_type
                : CommerceSourceType::from($link->source_type);
            $sourceId = (int) $link->source_id;
        } elseif ($line->product_variant_id) {
            $sourceType = CommerceSourceType::ProductVariant;
            $sourceId = (int) $line->product_variant_id;
        } elseif ($line->product_id) {
            $sourceType = CommerceSourceType::Product;
            $sourceId = (int) $line->product_id;
        }

        if (! $sourceType || ! $sourceId) {
            return;
        }

        $absQty = $this->commerce->assertIntegerQuantity($this->commerceQtyOrFail($line, $qty));
        $delta = $increase ? $absQty : -$absQty;
        $key = sprintf('stock-tx:%d:line:%d:commerce', $tx->id, $line->id);

        $typeValue = $tx->transaction_type instanceof StockTransactionType
            ? $tx->transaction_type->value
            : (string) $tx->transaction_type;

        $adjustment = $this->commerce->adjust(
            $sourceType,
            $sourceId,
            $delta,
            'stock_transaction_'.$typeValue,
            $key,
            'stock_transaction',
            $tx->document_number,
            $tx->getMorphClass(),
            $tx->id,
        );

        // الربط مع سجل التعديل عبر reference على CommerceQuantityAdjustment وليس عمودًا على السطر
        unset($adjustment);

        $line->affects_commerce_quantity = true;
        $line->commerce_quantity_delta = (string) $delta;
        $line->save();
    }
}
