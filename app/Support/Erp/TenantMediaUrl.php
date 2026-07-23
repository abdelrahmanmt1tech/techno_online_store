<?php

namespace App\Support\Erp;

/**
 * روابط وسائط Tenant وفق AGENTS.md — لا تستخدم asset('storage/'.$path).
 */
final class TenantMediaUrl
{
    public static function make(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        $path = ltrim(str_replace('\\', '/', $path), '/');

        // إن وُجد المسار كاملًا بالفعل
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $tenantId = tenant('id');
        if (! $tenantId) {
            return asset('storage/'.$path);
        }

        if (str_starts_with($path, 'tenant'.$tenantId.'/')) {
            return asset('storage/'.$path);
        }

        return asset('storage/tenant'.$tenantId.'/'.$path);
    }
}
