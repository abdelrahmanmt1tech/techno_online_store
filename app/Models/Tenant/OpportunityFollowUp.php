<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpportunityFollowUp extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected $fillable = [
        'opportunity_id',
        'follow_up_type_id',
        'follow_up_status_id',
        'assigned_to',
        'created_by',
        'parent_follow_up_id',
        'next_follow_up_at',
        'scheduled_at',
        'completed_at',
        'offer_text',
        'customer_reply',
        'internal_notes',
        'meta',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }


    public function followUpType(): BelongsTo
    {
        return $this->belongsTo(FollowUpType::class);
    }

    public function followUpStatus(): BelongsTo
    {
        return $this->belongsTo(FollowUpStatus::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'created_by');
    }

    public function parentFollowUp(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_follow_up_id');
    }

    public function childFollowUps(): HasMany
    {
        return $this->hasMany(self::class, 'parent_follow_up_id');
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

    public function scopeScheduled(Builder $query): Builder
    {
        return $query
            ->whereNull('completed_at')
            ->where('scheduled_at', '>=', now());
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNull('completed_at')
            ->where('scheduled_at', '<', now());
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    public function getSchedulingStateAttribute(): string
    {
        if ($this->completed_at !== null) {
            return 'completed';
        }

        if ($this->scheduled_at === null) {
            return 'scheduled';
        }

        return $this->scheduled_at->isPast() ? 'overdue' : 'scheduled';
    }

    protected function casts(): array
    {
        return [
            'next_follow_up_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'meta' => 'array',
        ];
    }
}
