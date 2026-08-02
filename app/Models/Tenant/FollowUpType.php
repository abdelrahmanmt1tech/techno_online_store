<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class FollowUpType extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;
    use HasTranslations;
    public array $translatable = ['name'];
    protected $fillable = [
        'name',
        'icon',
    ];

    public function opportunityFollowUps(): HasMany
    {
        return $this->hasMany(OpportunityFollowUp::class, 'follow_up_type_id');
    }
}
