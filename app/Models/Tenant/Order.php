<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'order_number',
        'token',
        'cart_id',
        'customer_id',
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'governorate_id',
        'governorate_name',
        'shipping_cost',
        'coupon_id',
        'coupon_code',
        'discount',
        'subtotal',
        'total',
        'notes',
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->token)) {
                $order->token = Str::uuid()->toString();
            }
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $lastOrder = static::orderBy('id', 'desc')->first();
        $nextNumber = $lastOrder ? ((int) substr($lastOrder->order_number, 1) + 1) : 1001;

        return '#'.$nextNumber;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function salesInvoices(): HasMany
    {
        return $this->hasMany(SalesInvoice::class);
    }
}
