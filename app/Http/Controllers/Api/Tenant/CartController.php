<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\AddCartItemRequest;
use App\Http\Requests\Api\Tenant\ApplyCouponRequest;
use App\Http\Requests\Api\Tenant\SetGovernorateRequest;
use App\Http\Requests\Api\Tenant\UpdateCartItemRequest;
use App\Http\Resources\Tenant\CartResource;
use App\Models\Tenant\Cart;
use App\Models\Tenant\CartItem;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Traits\ApiResponse;
use Illuminate\Support\Str;

class CartController extends Controller
{
    use ApiResponse;

    public function addItem(AddCartItemRequest $request)
    {
        $token = $request->input('cart_token');
        $cart = $token ? Cart::where('token', $token)->first() : null;

        if (! $cart) {
            $token = Str::uuid()->toString();
            $cart = Cart::create([
                'token' => $token,
                'session_id' => session()->getId(),
                'status' => 'active',
            ]);
        }

        return $this->handleAddItem($request, $cart, $token);
    }

    public function show(string $token)
    {
        $cart = Cart::where('token', $token)
            ->with([
                'items.product' => fn ($q) => $q->with('media'),
                'items.variant.options.variation',
                'governorate',
            ])
            ->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        return $this->successResponse(new CartResource($cart));
    }

    private function handleAddItem(AddCartItemRequest $request, Cart $cart, string $token)
    {
        if ($cart->status !== 'active') {
            return $this->errorResponse('Cart is no longer active', 422);
        }

        $validated = $request->validated();

        $product = Product::findOrFail($validated['product_id']);

        if (! $product->is_active) {
            return $this->errorResponse('Product is not available', 422);
        }

        $variant = ProductVariant::where('id', $validated['product_variant_id'])
            ->where('product_id', $product->id)
            ->first();

        if (! $variant) {
            return $this->errorResponse('Variant not found for this product', 422);
        }

        if ($variant->is_active === false) {
            return $this->errorResponse('Variant is not available', 422);
        }

        if ($product->track_stock && $variant->quantity < $validated['quantity']) {
            return $this->errorResponse('Insufficient stock', 422);
        }

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('product_variant_id', $validated['product_variant_id'])
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $validated['quantity'],
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $validated['product_variant_id'],
                'quantity' => $validated['quantity'],
            ]);
        }

        $cart->load([
            'items.product' => fn ($q) => $q->with('media'),
            'items.variant.options.variation',
            'governorate',
        ]);

        return $this->successResponse(
            new CartResource($cart->append('token')),
            __('messages.resource_created_successfully'),
        );
    }

    public function updateItem(UpdateCartItemRequest $request, string $token, string $item)
    {
        $cart = Cart::where('token', $token)->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($cart->status !== 'active') {
            return $this->errorResponse('Cart is no longer active', 422);
        }

        $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $item)->first();

        if (! $cartItem) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $validated = $request->validated();

        if ($cartItem->product->track_stock) {
            $available = $cartItem->variant
                ? $cartItem->variant->quantity
                : $cartItem->product->quantity;

            if ($available < $validated['quantity']) {
                return $this->errorResponse('Insufficient stock', 422);
            }
        }

        $cartItem->update([
            'quantity' => $validated['quantity'],
        ]);

        return $this->successResponse(null, __('messages.success'));
    }

    public function removeItem(string $token, string $item)
    {
        $cart = Cart::where('token', $token)->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $cartItem = CartItem::where('cart_id', $cart->id)->where('id', $item)->first();

        if (! $cartItem) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $cartItem->delete();

        if ($cart->items()->count() === 0) {
            $cart->delete();
        }

        return $this->successResponse(null, __('messages.success'));
    }

    public function setGovernorate(SetGovernorateRequest $request, string $token)
    {
        $cart = Cart::where('token', $token)->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($cart->status !== 'active') {
            return $this->errorResponse('Cart is no longer active', 422);
        }

        $validated = $request->validated();

        $governorate = Governorate::findOrFail($validated['governorate_id']);

        if (! $governorate->is_active) {
            return $this->errorResponse('Governorate is not available for shipping', 422);
        }

        $cart->update([
            'governorate_id' => $governorate->id,
        ]);

        return $this->successResponse(null, __('messages.success'));
    }

    public function applyCoupon(ApplyCouponRequest $request, string $token)
    {
        $cart = Cart::where('token', $token)
            ->with(['items.variant', 'items.product'])
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

        $coupon = Coupon::where('code', strtoupper($validated['code']))->first();

        if (! $coupon) {
            return $this->errorResponse('Invalid coupon code', 422);
        }

        if (! $coupon->isValid()) {
            return $this->errorResponse('Coupon is not valid or has expired', 422);
        }

        $subtotal = $cart->items->sum(fn ($item) => $item->unitPrice() * $item->quantity);

        if ($subtotal < $coupon->minimum_order_amount) {
            return $this->errorResponse(
                'Minimum order amount for this coupon is '.number_format($coupon->minimum_order_amount, 2),
                422,
            );
        }

        $discount = $coupon->calculateDiscount($subtotal);

        return $this->successResponse([
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'total' => max(0, $subtotal - $discount),
        ], 'Coupon applied successfully');
    }
}
