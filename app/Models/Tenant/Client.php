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

    protected static function booted(): void
    {
        static::saved(function (Client $model): void {
            $model->accTree();
        });
    }

    public function accountTree(): BelongsTo
    {
        return $this->belongsTo(AccountTree::class, 'account_tree_id');
    }

    public function accountsCenter(): BelongsTo
    {
        return $this->belongsTo(AccountsCenter::class, 'accounts_center_id');
    }

    /**
     * Sync leaf account under clients parent from TenantSetting.
     */
    public function accTree(): void
    {
        if ($this->stage === ClientStage::LEAD) {
            return;
        }

        $parentId = TenantSetting::getValue('clients_account_tree_id');
        if (! $parentId) {
            return;
        }

        $displayName = trim((string) $this->name);
        if ($displayName === '' && ! empty($this->company_name)) {
            $displayName = trim((string) $this->company_name);
        }
        if ($displayName === '' && ! empty($this->gondc_name)) {
            $displayName = trim((string) $this->gondc_name);
        }
        if ($displayName === '') {
            $displayName = 'عميل #'.$this->id;
        }

        $acc = AccountTree::updateOrCreate(
            [
                'account_code' => 'CLIENT#'.$this->id,
            ],
            [
                'parent_id' => (int) $parentId,
                'account_name' => $displayName,
                'account_code' => 'CLIENT#'.$this->id,
                'account_type' => 'debit',
                'main_acc_status' => 'sub',
            ]
        );

        if ((int) ($this->account_tree_id ?? 0) !== (int) $acc->id) {
            $this->account_tree_id = $acc->id;
            $this->saveQuietly();
        } elseif ((int) ($this->account_tree_id ?? 0) > 0) {
            AccountTree::query()
                ->whereKey($this->account_tree_id)
                ->update(['account_name' => $displayName]);
        }
    }

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
