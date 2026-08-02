<?php

namespace App\Models\Tenant;

use App\Enums\Crm\OpportunityStageAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class OpportunityStage extends Model
{
    use Concerns\BelongsToTenantConnection;

    use HasTranslations;
    use SoftDeletes;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'color',
        'action',
        'is_final',
        'sort_order',
    ];

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'opportunity_stage_id');
    }

    public function stageLogsFrom(): HasMany
    {
        return $this->hasMany(OpportunityStageLog::class, 'from_stage_id');
    }

    public function stageLogsTo(): HasMany
    {
        return $this->hasMany(OpportunityStageLog::class, 'to_stage_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    public function scopeFinal(Builder $query): Builder
    {
        return $query->where('is_final', true);
    }

    protected function casts(): array
    {
        return [
            'action' => OpportunityStageAction::class,
            'is_final' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
