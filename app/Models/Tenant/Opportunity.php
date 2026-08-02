<?php

namespace App\Models\Tenant;

use App\Enums\Crm\OpportunityStageAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use SoftDeletes;

    use Concerns\BelongsToTenantConnection;

    protected $fillable = [
        'client_id',
        'created_by',
        'opportunity_stage_id',
        'title',
        'amount',
        'agreed_amount',
        'description',
        'is_closed',
        'closed_at',
        'assigned_to',
        'first_assigned_to',
        'campaign_id',
        'branch_id',
        'meta',
    ];

    protected static function booted(): void
    {
        static::saving(function (Opportunity $opportunity): void {
            if (! $opportunity->opportunity_stage_id) {
                return;
            }

            if ($opportunity->exists && ! $opportunity->isDirty('opportunity_stage_id')) {
                return;
            }

            $stage = OpportunityStage::query()->find($opportunity->opportunity_stage_id);

            if (! $stage) {
                return;
            }

            $opportunity->applyStageAction($stage->action);
        });
    }

    public function applyStageAction(OpportunityStageAction|string|null $action): void
    {
        if (is_string($action)) {
            $action = OpportunityStageAction::tryFrom($action) ?? OpportunityStageAction::NONE;
        }

        match ($action ?? OpportunityStageAction::NONE) {
            OpportunityStageAction::SUCCESS_CLOSE, OpportunityStageAction::FAILED_CLOSE => $this->closeFromStageAction(),
            OpportunityStageAction::REOPEN, OpportunityStageAction::OPEN => $this->reopenFromStageAction(),
            default => null,
        };
    }

    protected function closeFromStageAction(): void
    {
        $this->is_closed = true;
        $this->closed_at = now();
    }

    protected function reopenFromStageAction(): void
    {
        $this->is_closed = false;
        $this->closed_at = null;
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function opportunityFollowUps(): HasMany
    {
        return $this->hasMany(OpportunityFollowUp::class, 'opportunity_id');
    }

    public function latestFollowUp(): HasOne
    {
        return $this->hasOne(OpportunityFollowUp::class)->latestOfMany();
    }

    public function oldestFollowUp(): HasOne
    {
        return $this->hasOne(OpportunityFollowUp::class)->oldestOfMany();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TenantUser::class, 'created_by');
    }

    public function opportunityStage(): BelongsTo
    {
        return $this->belongsTo(OpportunityStage::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TenantUser::class, 'assigned_to');
    }

    public function firstAssignedTo(): BelongsTo
    {
        return $this->belongsTo(\App\Models\TenantUser::class, 'first_assigned_to');
    }

    public function opportunityStageLogs(): HasMany
    {
        return $this->hasMany(OpportunityStageLog::class, 'opportunity_id');
    }

    public function opportunityAssignmentLogs(): HasMany
    {
        return $this->hasMany(OpportunityAssignmentLog::class, 'opportunity_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function latestNote(): MorphOne
    {
        return $this->morphOne(Note::class, 'noteable')
            ->latestOfMany();
    }

    public function opportunityCommissions(): HasMany
    {
        return $this->hasMany(OpportunityCommission::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_closed', false);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('is_closed', true);
    }

    public function scopeWon(Builder $query): Builder
    {
        return $query->closed()->whereHas('opportunityStage', fn (Builder $q) => $q->where('action', 'success_close'));
    }

    public function scopeLost(Builder $query): Builder
    {
        return $query->closed()->whereHas('opportunityStage', fn (Builder $q) => $q->where('action', 'failed_close'));
    }

    protected function casts(): array
    {
        return [
            'is_closed' => 'boolean',
            'closed_at' => 'datetime',
            'amount' => 'decimal:2',
            'agreed_amount' => 'decimal:2',
            'meta' => 'array',
        ];
    }
}
