<?php

namespace App\Models\Tenant;

use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'user_id',
        'company_name',
        'gondc_name',
        'email',
        'phone',
        'tax_number',
        'commercial_register',
        'address',
        'stage',
        'lead_source_id',
        'sales_rep_id',
        'first_followed_by',
        'commission_amount',
        'is_provisional',
        'account_tree_id',
        'accounts_center_id',
        'credit_limit',
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
        return $this->primaryContact('email')?->value ?? $this->email;
    }

    public function primaryPhone(): ?string
    {
        return $this->primaryContact('phone')?->value ?? $this->phone;
    }

    public function primaryWhatsapp(): ?string
    {
        return $this->primaryContact('whatsapp')?->value;
    }
}
