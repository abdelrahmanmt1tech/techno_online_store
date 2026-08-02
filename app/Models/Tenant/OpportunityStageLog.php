<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityStageLog extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected $fillable = [
        'opportunity_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class, 'to_stage_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'changed_by');
    }
}
