<?php

namespace App\Models\Tenant;

use App\Enums\Pos\ReceiptNumberStrategy;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosSetting extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'receipt_number_strategy',
        'default_currency',
        'require_open_session',
        'allow_suspend_sales',
        'allow_negative_stock',
        'meta',
        'updated_by',
    ];

    protected $casts = [
        'receipt_number_strategy' => ReceiptNumberStrategy::class,
        'require_open_session' => 'boolean',
        'allow_suspend_sales' => 'boolean',
        'allow_negative_stock' => 'boolean',
        'meta' => 'array',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }
}
