<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Cart;
use App\Models\Tenant\CartItem;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    use ApiResponse;

    public function store(Request $request)
    {
        $cart = Cart::create([
            'token' => Str::uuid()->toString(),
            'session_id' => $request->input('session_id'),
        ]);

        return $this->createdResponse([
            'token' => $cart->token,
        ]);
    }

    public function show(string $token)
    {
        $cart = Cart::where('token', $token)
            ->with([
                'items.product' => fn ($q) => $q->with('media'),
                'items.variant',
                'governorate',
                'coupon',
            ])
            ->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        return $this->successResponse([
            'token' => $cart->token,
            'subtotal' => $cart->subtotal,
            'discount' => $cart->discount,
            'shipping_cost' => $cart->shipping_cost,
            'total' => $cart->total,
            'status' => $cart->status,
            'governorate' => $cart->governorate ? [
                'id' => $cart->governorate->id,
                'name' => $cart->governorate->name,
                'shipping_cost' => $cart->governorate->shipping_cost,
            ] : null,
            'coupon' => $cart->coupon ? [
                'code' => $cart->coupon->code,
                'type' => $cart->coupon->type,
                'value' => $cart->coupon->value,
            ] : null,
            'items' => $cart->items->map(fn ($item) => [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'product' => [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'price' => $item->product->price,
                    'sale_price' => $item->product->sale_price,
                    'media' => $item->product->media->map(fn ($m) => [
                        'file' => asset('storage/'.$m->file),
                        'type' => $m->type,
                    ]),
                ],
                'variant' => $item->variant ? [
                    'id' => $item->variant->id,
                    'price' => $item->variant->price,
                    'sale_price' => $item->variant->sale_price,
                    'sku' => $item->variant->sku,
                    'options' => $item->variant->options->map(fn ($o) => [
                        'value' => $o->value,
                        'variation_name' => $o->variation->name ?? null,
                    ]),
                ] : null,
            ]),
        ]);
    }

    public function addItem(Request $request, string $token)
    {
        $cart = Cart::where('token', $token)->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($cart->status !== 'active') {
            return $this->errorResponse('Cart is no longer active', 422);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if (! $product->is_active) {
            return $this->errorResponse('Product is not available', 422);
        }

        $variant = null;
        if (isset($validated['product_variant_id'])) {
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
        } else {
            if ($product->track_stock && $product->quantity < $validated['quantity']) {
                return $this->errorResponse('Insufficient stock', 422);
            }
        }

        $unitPrice = $variant ? ($variant->sale_price ?? $variant->price) : ($product->sale_price ?? $product->price);

        $existingItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('product_variant_id', $validated['product_variant_id'] ?? null)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $validated['quantity'],
                'unit_price' => $unitPrice,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'product_variant_id' => $validated['product_variant_id'] ?? null,
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $validated['quantity'],
            ]);
        }

        $cart->recalculate();

        return $this->successResponse(null, __('messages.resource_created_successfully'));
    }

    public function updateItem(Request $request, string $token, string $item)
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

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

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

        $cart->recalculate();

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
        $cart->recalculate();

        return $this->successResponse(null, __('messages.success'));
    }

    public function setGovernorate(Request $request, string $token)
    {
        $cart = Cart::where('token', $token)->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($cart->status !== 'active') {
            return $this->errorResponse('Cart is no longer active', 422);
        }

        $validated = $request->validate([
            'governorate_id' => 'required|exists:governorates,id',
        ]);

        $governorate = Governorate::findOrFail($validated['governorate_id']);

        if (! $governorate->is_active) {
            return $this->errorResponse('Governorate is not available for shipping', 422);
        }

        $cart->update([
            'governorate_id' => $governorate->id,
            'shipping_cost' => $governorate->shipping_cost,
        ]);

        $cart->recalculate();

        return $this->successResponse(null, __('messages.success'));
    }

    public function applyCoupon(Request $request, string $token)
    {
        $cart = Cart::where('token', $token)->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        if ($cart->status !== 'active') {
            return $this->errorResponse('Cart is no longer active', 422);
        }

        if ($cart->items->isEmpty()) {
            return $this->errorResponse('Cart is empty', 422);
        }

        $validated = $request->validate([
            'code' => 'required|string',
        ]);

        $coupon = Coupon::where('code', strtoupper($validated['code']))->first();

        if (! $coupon) {
            return $this->errorResponse('Invalid coupon code', 422);
        }

        $customerIdentifier = $cart->session_id ?? $request->input('customer_identifier');

        if ($customerIdentifier && ! $coupon->isUsableBy($customerIdentifier)) {
            return $this->errorResponse('Coupon is not valid or has reached its usage limit', 422);
        }

        if (! $coupon->isValid()) {
            return $this->errorResponse('Coupon is not valid or has expired', 422);
        }

        $subtotal = $cart->items->sum(fn ($item) => $item->unit_price * $item->quantity);

        if ($subtotal < $coupon->minimum_order_amount) {
            return $this->errorResponse(
                'Minimum order amount for this coupon is '.number_format($coupon->minimum_order_amount, 2),
                422,
            );
        }

        $cart->update([
            'coupon_id' => $coupon->id,
        ]);

        $cart->recalculate();

        return $this->successResponse([
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount' => $cart->discount,
            'total' => $cart->total,
        ], 'Coupon applied successfully');
    }

    public function removeCoupon(string $token)
    {
        $cart = Cart::where('token', $token)->first();

        if (! $cart) {
            return $this->notFoundResponse(__('messages.resource_not_found'));
        }

        $cart->update([
            'coupon_id' => null,
            'discount' => 0,
        ]);

        $cart->recalculate();

        return $this->successResponse(null, 'Coupon removed successfully');
    }
}
