<?php

namespace App\Models\Tenant;

use App\Models\Tenant\Concerns\BelongsToTenantConnection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrAttendanceLocation extends Model
{
    use BelongsToTenantConnection;

    protected $table = 'hr_attendance_locations';

    protected $fillable = [
        'name',
        'branch_id',
        'latitude',
        'longitude',
        'allowed_radius_meters',
        'minimum_accuracy_meters',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(HrEmployee::class, 'attendance_location_id');
    }
}
