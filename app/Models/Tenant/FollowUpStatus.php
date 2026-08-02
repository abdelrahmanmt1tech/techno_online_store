<?php

namespace App\Models\Tenant;

use App\Enums\Crm\FollowUpStatusAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class FollowUpStatus extends Model
{
    use Concerns\BelongsToTenantConnection;

    use HasTranslations;
    use SoftDeletes;

    public array $translatable = ['name'];

    protected $fillable = [
        'name',
        'color',
        'action',
    ];

    public function opportunityFollowUps(): HasMany
    {
        return $this->hasMany(OpportunityFollowUp::class, 'follow_up_status_id');
    }

    protected function casts(): array
    {
        return [
            'action' => FollowUpStatusAction::class,
        ];
    }
}
