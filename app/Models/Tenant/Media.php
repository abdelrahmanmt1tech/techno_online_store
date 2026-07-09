<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'file',
        'type',
    ];

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
