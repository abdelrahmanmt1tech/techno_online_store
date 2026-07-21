<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Tenant\SendCheckoutOtpRequest;
use App\Http\Requests\Api\Tenant\VerifyCheckoutOtpRequest;
use App\Http\Resources\Tenant\CheckoutResource;
use App\Mail\CheckoutOtpMail;
use App\Models\Tenant\Cart;
use App\Models\Tenant\Coupon;
use App\Models\Tenant\CouponUsage;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerContact;
use App\Models\Tenant\Governorate;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutOtpController extends Controller
{
    use ApiResponse;

    private const OTP_TTL_MINUTES = 10;

    public function sendOtp(SendCheckoutOtpRequest $request, string $token)
    {
        $cart = Cart::where('token', $token)
            ->with(['items'])
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
        $code = (string) random_int(100000, 999999);
        $cacheKey = $this->otpCacheKey($token, $validated['email']);

        Cache::put($cacheKey, $code, now()->addMinutes(self::OTP_TTL_MINUTES));

        Mail::to($validated['email'])->send(new CheckoutOtpMail($code, self::OTP_TTL_MINUTES));

        return $this->successResponse(null, __('auth.verification_code_sent'));
    }

    public function verifyAndCheckout(VerifyCheckoutOtpRequest $request, string $token)
    {
        $cart = Cart::where('token', $token)
            ->with(['items.product', 'items.variant.options.variation', 'governorate'])
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
        $cacheKey = $this->otpCacheKey($token, $validated['email']);
        $storedCode = Cache::get($cacheKey);

        if (! $storedCode || $storedCode !== $validated['code']) {
            return $this->errorResponse(__('auth.invalid_or_expired_code'), 422);
        }

        Cache::forget($cacheKey);

        $governorate = isset($validated['governorate_id'])
            ? Governorate::find($validated['governorate_id'])
            : $cart->governorate;

        if (! $governorate) {
            return $this->errorResponse('Please select a governorate for shipping', 422);
        }

        $subtotal = $cart->items->sum(fn ($item) => $item->unitPrice() * $item->quantity);

        $discount = 0;
        $coupon = null;

        if (! empty($validated['coupon_code'])) {
            $coupon = Coupon::where('code', strtoupper($validated['coupon_code']))->first();

            if (! $coupon) {
                return $this->errorResponse('Invalid coupon code', 422);
            }

            if (! $coupon->isValid()) {
                return $this->errorResponse('Coupon is not valid or has expired', 422);
            }

            if ($subtotal < $coupon->minimum_order_amount) {
                return $this->errorResponse('Coupon minimum order amount not met', 422);
            }

            $customerIdentifier = $cart->session_id ?? $validated['email'];
            if (! $coupon->isUsableBy($customerIdentifier)) {
                return $this->errorResponse('Coupon is no longer valid', 422);
            }

            $discount = $coupon->calculateDiscount($subtotal);
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
            $coupon,
            $request,
        ) {
            $customer = $this->findOrCreateCustomer(
                $validated['email'],
                $validated['customer_name'],
                $validated['customer_phone'],
            );

            $userId = $request->user()?->id;

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'token' => Str::uuid()->toString(),
                'cart_id' => $cart->id,
                'customer_id' => $customer?->id,
                'user_id' => $userId,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => $validated['payment_method'] === 'online' ? 'paid' : 'unpaid',
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['email'],
                'customer_address' => $validated['customer_address'],
                'governorate_id' => $governorate?->id,
                'governorate_name' => $governorate?->name ?? '',
                'shipping_cost' => $shippingCost,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'total' => $total,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($cart->items as $cartItem) {
                $unitPrice = $cartItem->unitPrice();

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
                    'unit_price' => $unitPrice,
                ]);

                if ($cartItem->product->track_stock) {
                    if ($cartItem->variant) {
                        $cartItem->variant->decrement('quantity', $cartItem->quantity);
                    } else {
                        $cartItem->product->decrement('quantity', $cartItem->quantity);
                    }
                }
            }

            if ($coupon) {
                $customerIdentifier = $cart->session_id ?? $validated['email'];

                CouponUsage::create([
                    'coupon_id' => $coupon->id,
                    'order_id' => $order->id,
                    'customer_identifier' => $customerIdentifier,
                    'discount_amount' => $discount,
                ]);

                $coupon->increment('usage_count');
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

    private function findOrCreateCustomer(string $email, string $name, string $phone): ?Customer
    {
        $contact = CustomerContact::where('type', 'email')
            ->where('value', $email)
            ->first();

        if ($contact) {
            return $contact->customer;
        }

        $customer = Customer::create(['name' => $name]);

        $customer->contacts()->create([
            'type' => 'email',
            'value' => $email,
            'verified_at' => now(),
            'is_primary' => true,
        ]);

        $customer->contacts()->create([
            'type' => 'phone',
            'value' => $phone,
            'is_primary' => true,
        ]);

        return $customer;
    }

    private function otpCacheKey(string $token, string $email): string
    {
        return 'checkout_otp:'.hash('sha256', $token.$email);
    }
}
