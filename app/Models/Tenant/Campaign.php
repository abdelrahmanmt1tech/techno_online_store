<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Campaign extends Model
{
    use Concerns\BelongsToTenantConnection;

    use HasTranslations;
    use SoftDeletes;

    public array $translatable = [
        'name',
        'description',
    ];

    protected $fillable = [
        'name',
        'description',
        'budget',
        'start_date',
        'end_date',
        'status',
    ];

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class, 'campaign_id');
    }

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
