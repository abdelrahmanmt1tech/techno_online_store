<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'code',
        'type',
        'value',
        'minimum_order_amount',
        'maximum_discount_amount',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'minimum_order_amount' => 'decimal:2',
        'maximum_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_user_limit' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            $coupon->code = strtoupper($coupon->code);
        });
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isUsableBy(string $customerIdentifier): bool
    {
        if (! $this->isValid()) {
            return false;
        }

        if ($this->per_user_limit !== null) {
            $userUsageCount = $this->usages()
                ->where('customer_identifier', $customerIdentifier)
                ->count();

            if ($userUsageCount >= $this->per_user_limit) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($subtotal < $this->minimum_order_amount) {
            return 0;
        }

        if ($this->type === 'percentage') {
            $discount = $subtotal * ($this->value / 100);

            if ($this->maximum_discount_amount !== null) {
                $discount = min($discount, $this->maximum_discount_amount);
            }
        } else {
            $discount = min($this->value, $subtotal);
        }

        return round($discount, 2);
    }
}
