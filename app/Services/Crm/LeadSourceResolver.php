<?php

namespace App\Services\Crm;

use App\Models\Tenant\LeadSource;

class LeadSourceResolver
{
    /**
     * @param  array{ar: string, en: string}  $names
     */
    public function resolveByKey(string $key, array $names): LeadSource
    {
        $existing = LeadSource::query()->where('key', $key)->first();

        if ($existing) {
            return $existing;
        }

        return LeadSource::query()->create([
            'key' => $key,
            'name' => $names,
        ]);
    }
}
