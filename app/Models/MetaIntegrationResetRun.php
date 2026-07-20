<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class MetaIntegrationResetRun extends Model
{
    use CentralConnection;

    public const STATUS_PREVIEWED = 'previewed';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIALLY_FAILED = 'partially_failed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'meta_integration_reset_runs';

    protected $fillable = [
        'requested_by',
        'scope',
        'status',
        'previewed_at',
        'started_at',
        'completed_at',
        'tenants_total',
        'tenants_succeeded',
        'tenants_failed',
        'central_rows_deleted',
        'tenant_rows_deleted',
        'summary',
        'errors',
    ];

    protected function casts(): array
    {
        return [
            'previewed_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'summary' => 'array',
            'errors' => 'array',
            'tenants_total' => 'integer',
            'tenants_succeeded' => 'integer',
            'tenants_failed' => 'integer',
            'central_rows_deleted' => 'integer',
            'tenant_rows_deleted' => 'integer',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }
}
