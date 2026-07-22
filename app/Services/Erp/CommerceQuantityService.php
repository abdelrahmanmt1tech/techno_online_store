<?php

namespace App\Services\Erp;

use App\Enums\Erp\CommerceSourceType;
use App\Models\Tenant\CommerceQuantityAdjustment;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Support\Erp\Decimal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * تأثير صريح على كمية المتجر فقط — ليس مزامنة دائمة مع ERP.
 * Idempotent عبر idempotency_key فريد.
 */
final class CommerceQuantityService
{
    public function adjust(
        CommerceSourceType $sourceType,
        int $sourceId,
        int $delta,
        string $reason,
        string $idempotencyKey,
        ?string $documentType = null,
        ?string $documentNumber = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): CommerceQuantityAdjustment {
        $existing = CommerceQuantityAdjustment::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $existing;
        }

        if ($delta === 0) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.commerce_delta_nonzero'),
            ]);
        }

        return match ($sourceType) {
            CommerceSourceType::Product => $this->adjustProduct(
                $sourceId,
                $delta,
                $reason,
                $idempotencyKey,
                $documentType,
                $documentNumber,
                $referenceType,
                $referenceId,
            ),
            CommerceSourceType::ProductVariant => $this->adjustVariant(
                $sourceId,
                $delta,
                $reason,
                $idempotencyKey,
                $documentType,
                $documentNumber,
                $referenceType,
                $referenceId,
            ),
        };
    }

    public function assertIntegerQuantity(string $quantity): int
    {
        if (! Decimal::isIntegerQuantity($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.commerce_quantity_must_be_integer'),
            ]);
        }

        return Decimal::toIntQuantity($quantity);
    }

    private function adjustProduct(
        int $productId,
        int $delta,
        string $reason,
        string $idempotencyKey,
        ?string $documentType,
        ?string $documentNumber,
        ?string $referenceType,
        ?int $referenceId,
    ): CommerceQuantityAdjustment {
        /** @var Product $product */
        $product = Product::query()->whereKey($productId)->lockForUpdate()->firstOrFail();
        $before = (int) $product->quantity;
        $after = $before + $delta;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.insufficient_commerce_quantity', [
                    'available' => $before,
                    'requested' => abs($delta),
                ]),
            ]);
        }

        $product->quantity = $after;
        $product->save();

        return CommerceQuantityAdjustment::query()->create([
            'source_type' => CommerceSourceType::Product->value,
            'source_id' => $productId,
            'product_id' => $productId,
            'product_variant_id' => null,
            'quantity_before' => $before,
            'quantity_delta' => $delta,
            'quantity_after' => $after,
            'reason' => $reason,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'idempotency_key' => $idempotencyKey,
            'created_by' => Auth::guard('tenant')->id(),
        ]);
    }

    private function adjustVariant(
        int $variantId,
        int $delta,
        string $reason,
        string $idempotencyKey,
        ?string $documentType,
        ?string $documentNumber,
        ?string $referenceType,
        ?int $referenceId,
    ): CommerceQuantityAdjustment {
        /** @var ProductVariant $variant */
        $variant = ProductVariant::query()->whereKey($variantId)->lockForUpdate()->firstOrFail();
        $before = (int) $variant->quantity;
        $after = $before + $delta;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'quantity' => __('erp.validation.insufficient_commerce_quantity', [
                    'available' => $before,
                    'requested' => abs($delta),
                ]),
            ]);
        }

        $variant->quantity = $after;
        $variant->save();

        return CommerceQuantityAdjustment::query()->create([
            'source_type' => CommerceSourceType::ProductVariant->value,
            'source_id' => $variantId,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variantId,
            'quantity_before' => $before,
            'quantity_delta' => $delta,
            'quantity_after' => $after,
            'reason' => $reason,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'idempotency_key' => $idempotencyKey,
            'created_by' => Auth::guard('tenant')->id(),
        ]);
    }
}
