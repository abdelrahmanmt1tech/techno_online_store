<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityAssignmentLog extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected $fillable = [
        'opportunity_id',
        'from_user_id',
        'to_user_id',
        'changed_by',
        'notes',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'to_user_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'changed_by');
    }
}
