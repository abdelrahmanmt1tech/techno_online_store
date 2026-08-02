<?php

namespace App\Helper;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class CurrencyHelper
{
    private static ?array $cache = null;

    public static function getCurrency(): ?array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $currencyCode = Setting::where('key', 'site_currency')->value('value');

        if ($currencyCode) {
            $row = DB::connection(
                config('tenancy.database.central_connection', config('database.default'))
            )
                ->table('currencies')
                ->where('code', $currencyCode)
                ->where('is_active', true)
                ->first();
        }

        if (! isset($row) || ! $row) {
            return static::$cache = [
                'code' => 'USD',
                'name' => 'USD',
                'symbol' => '$',
            ];
        }

        $locale = app()->getLocale();
        $name = json_decode($row->name, true)[$locale] ?? $row->code;

        return static::$cache = [
            'code' => $row->code,
            'name' => $name,
            'symbol' => $row->symbol ?? null,
        ];
    }

    public static function flush(): void
    {
        static::$cache = null;
    }
}
