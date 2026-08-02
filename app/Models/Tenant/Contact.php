<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contact extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'email',
        'message',
        'read_at',
        'status',
        'phone',
        'branch_id',
        'job',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function getIsReadAttribute(): bool
    {
        return ! is_null($this->read_at);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
