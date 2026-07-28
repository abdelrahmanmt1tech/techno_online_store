<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosReceiptSequence extends Model
{
    use BelongsToTenantConnection;

    protected $fillable = [
        'branch_id',
        'pos_register_id',
        'sequence_date',
        'next_number',
    ];

    protected $casts = [
        'sequence_date' => 'date',
        'next_number' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }
}
