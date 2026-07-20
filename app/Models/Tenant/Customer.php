<?php

namespace App\Models\Tenant;

use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'user_id',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(CustomerContact::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class);
    }

    public function primaryContact(string $type): ?CustomerContact
    {
        return $this->contacts()->where('type', $type)->where('is_primary', true)->first()
            ?? $this->contacts()->where('type', $type)->first();
    }

    public function primaryEmail(): ?string
    {
        return $this->primaryContact('email')?->value;
    }

    public function primaryPhone(): ?string
    {
        return $this->primaryContact('phone')?->value;
    }

    public function primaryWhatsapp(): ?string
    {
        return $this->primaryContact('whatsapp')?->value;
    }
}
