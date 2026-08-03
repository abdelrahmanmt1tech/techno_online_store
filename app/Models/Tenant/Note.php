<?php

namespace App\Models\Tenant;

use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use Concerns\BelongsToTenantConnection;

    use SoftDeletes;

    protected $fillable = [
        'created_by',
        'note',
        'is_private',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->BelongsTo(TenantUser::class, 'created_by');
    }

    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeVisibleTo(Builder $query, TenantUser $user): Builder
    {
        return $query->where(function (Builder $q) use ($user): void {
            $q->where('is_private', false)
                ->orWhere('created_by', $user->id);
        });
    }

    protected function casts(): array
    {
        return [
            'is_private' => 'boolean',
        ];
    }
}
