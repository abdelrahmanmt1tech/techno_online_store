<?php

namespace App\Models\Tenant;

use App\Enums\Crm\ClientStage;
use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * CRM alias for Customer — same `customers` table.
 */
class Client extends Customer
{
    protected $table = 'customers';

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

    protected $casts = [
        'stage' => ClientStage::class,
        'is_provisional' => 'boolean',
        'commission_amount' => 'decimal:2',
        'credit_limit' => 'decimal:2',
    ];

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'sales_rep_id');
    }

    public function firstFollower(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'first_followed_by');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'client_id');
    }

    public function opportunityFollowUps(): HasManyThrough
    {
        return $this->hasManyThrough(OpportunityFollowUp::class, Opportunity::class, 'client_id', 'opportunity_id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    public function latestOpportunity()
    {
        return $this->hasOne(Opportunity::class, 'client_id')->latestOfMany();
    }

    public function resolveLastCompletedFollowUp(): ?OpportunityFollowUp
    {
        return $this->opportunityFollowUps()
            ->whereNotNull('completed_at')
            ->orderByDesc('completed_at')
            ->first();
    }

    public function resolveNextScheduledFollowUp(): ?OpportunityFollowUp
    {
        return $this->opportunityFollowUps()
            ->whereNull('completed_at')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->first();
    }
}
