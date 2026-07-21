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
        'status',
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
}
