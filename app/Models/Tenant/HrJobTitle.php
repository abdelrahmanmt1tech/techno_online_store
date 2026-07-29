<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrJobTitle extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_job_titles';

    protected $fillable = ['name', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function employees(): HasMany
    {
        return $this->hasMany(HrEmployee::class, 'job_title_id');
    }
}
