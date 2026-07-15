<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'token',
        'session_id',
        'governorate_id',
        'coupon_id',
        'subtotal',
        'discount',
        'shipping_cost',
        'total',
        'status',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Cart $cart) {
            if (empty($cart->token)) {
                $cart->token = Str::uuid()->toString();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function recalculate(): void
    {
        $subtotal = $this->items->sum(fn ($item) => $item->unit_price * $item->quantity);

        $discount = 0;

        if ($this->coupon) {
            $discount = $this->coupon->calculateDiscount($subtotal);
        }

        $this->update([
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount + $this->shipping_cost),
        ]);
    }
}
