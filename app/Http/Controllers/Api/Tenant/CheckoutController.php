<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\CheckoutRequest;
use App\Http\Resources\Tenant\CheckoutResource;
use App\Models\Tenant\Cart;
use App\Models\Tenant\CouponUsage;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function store(CheckoutRequest $request, string $token)
    {
        $cart = Cart::where('token', $token)
            ->with(['items.product', 'items.variant', 'governorate', 'coupon'])
            ->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($cart->status !== 'active') {
            return $this->errorResponse('Cart is no longer active', 422);
        }

        if ($cart->items->isEmpty()) {
            return $this->errorResponse('Cart is empty', 422);
        }

        $validated = $request->validated();

        $governorate = isset($validated['governorate_id'])
            ? $cart->governorate
            : $cart->governorate;

        if (isset($validated['governorate_id'])) {
            $governorate = Governorate::find($validated['governorate_id']);
        }

        $subtotal = $cart->items->sum(fn ($item) => $item->unit_price * $item->quantity);

        $discount = 0;
        if ($cart->coupon) {
            $discount = $cart->coupon->calculateDiscount($subtotal);

            if ($subtotal < $cart->coupon->minimum_order_amount) {
                return $this->errorResponse('Coupon minimum order amount not met', 422);
            }

            $customerIdentifier = $cart->session_id ?? $request->input('customer_identifier');
            if ($customerIdentifier && ! $cart->coupon->isUsableBy($customerIdentifier)) {
                return $this->errorResponse('Coupon is no longer valid', 422);
            }
        }

        $shippingCost = $governorate?->shipping_cost ?? 0;
        $total = max(0, $subtotal - $discount + $shippingCost);

        $result = DB::transaction(function () use (
            $cart,
            $validated,
            $governorate,
            $subtotal,
            $discount,
            $shippingCost,
            $total,
            $request,
        ) {
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'token' => Str::uuid()->toString(),
                'cart_id' => $cart->id,
                'status' => 'pending',
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'customer_address' => $validated['customer_address'],
                'governorate_id' => $governorate?->id,
                'governorate_name' => $governorate?->name ?? '',
                'shipping_cost' => $shippingCost,
                'coupon_id' => $cart->coupon_id,
                'coupon_code' => $cart->coupon?->code,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $cartItem->variant?->sku ?? $cartItem->product->sku,
                    'variant_options' => $cartItem->variant
                        ? $cartItem->variant->options->mapWithKeys(fn ($o) => [
                            $o->variation->name ?? 'Option' => $o->value,
                        ])->toArray()
                        : null,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                ]);

                if ($cartItem->product->track_stock) {
                    if ($cartItem->variant) {
                        $cartItem->variant->decrement('quantity', $cartItem->quantity);
                    } else {
                        $cartItem->product->decrement('quantity', $cartItem->quantity);
                    }
                }
            }

            if ($cart->coupon) {
                $customerIdentifier = $cart->session_id ?? $request->input('customer_identifier');

                CouponUsage::create([
                    'coupon_id' => $cart->coupon->id,
                    'order_id' => $order->id,
                    'customer_identifier' => $customerIdentifier,
                    'discount_amount' => $discount,
                ]);

                $cart->coupon->increment('usage_count');
            }

            $cart->update(['status' => 'converted']);

            return [
                'token' => $order->token,
                'order_number' => $order->order_number,
                'total' => $order->total,
            ];
        });

        return $this->createdResponse(new CheckoutResource($result), 'Order placed successfully');
    }
}
