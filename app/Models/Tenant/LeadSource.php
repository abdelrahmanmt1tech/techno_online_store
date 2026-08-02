<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class LeadSource extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;
    use HasTranslations;

    public array $translatable = ['name'];

    protected $fillable = [
        'key',
        'name',
    ];

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    public function opportunities(): HasManyThrough
    {
        return $this->hasManyThrough(
            Opportunity::class,
            Client::class,
            'lead_source_id',
            'client_id',
        );
    }

    public function scopeWithReportingStats(Builder $query): Builder
    {
        return $query
            ->withCount('clients')
            ->withCount('opportunities')
            ->withCount(['opportunities as won_opportunities_count' => fn (Builder $q) => $q->won()])
            ->withSum(['opportunities as won_agreed_amount_total' => fn (Builder $q) => $q->won()], 'agreed_amount');
    }
}
