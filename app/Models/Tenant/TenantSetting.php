<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use Concerns\BelongsToTenantConnection;

    protected $table = 'tenant_settings';

    protected $fillable = [
        'key',
        'value',
        'string_value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        if (! $row) {
            return $default;
        }

        return $row->value ?? $row->string_value ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_scalar($value) || $value === null ? $value : json_encode($value)],
        );
    }
}
