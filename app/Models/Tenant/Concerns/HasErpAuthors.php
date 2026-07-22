<?php

namespace App\Models\Tenant\Concerns;

use App\Models\TenantUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait HasErpAuthors
{
    public static function bootHasErpAuthors(): void
    {
        static::creating(function ($model): void {
            $userId = Auth::guard('tenant')->id();
            if ($userId && empty($model->created_by)) {
                $model->created_by = $userId;
            }
            if ($userId && in_array('updated_by', $model->getFillable(), true) && empty($model->updated_by)) {
                $model->updated_by = $userId;
            }
        });

        static::updating(function ($model): void {
            $userId = Auth::guard('tenant')->id();
            if ($userId && in_array('updated_by', $model->getFillable(), true)) {
                $model->updated_by = $userId;
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }
}
