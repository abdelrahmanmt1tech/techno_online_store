<?php

namespace App\Models\Tenant;

use App\Enums\Erp\DocumentSequenceType;
use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequence extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'document_type',
        'branch_id',
        'prefix',
        'padding',
        'next_number',
    ];

    protected $casts = [
        'document_type' => DocumentSequenceType::class,
        'padding' => 'integer',
        'next_number' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
