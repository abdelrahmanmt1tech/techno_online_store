<?php

namespace App\Models\Tenant;

use App\Enums\Pos\CashierSessionStatus;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use App\Models\Tenant\Concerns\HasErpAuthors;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PosRegister extends Model
{
    use BelongsToTenantConnection;
    use HasErpAuthors;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'cash_drawer_id',
        'name',
        'code',
        'receipt_prefix',
        'is_active',
        'settings',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function cashDrawer(): BelongsTo
    {
        return $this->belongsTo(CashDrawer::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CashierSession::class);
    }

    public function openSession(): ?CashierSession
    {
        return $this->sessions()
            ->whereIn('status', [
                CashierSessionStatus::Opening->value,
                CashierSessionStatus::Opened->value,
                CashierSessionStatus::Closing->value,
            ])
            ->latest('opened_at')
            ->first();
    }
}
