<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Modules\TenantModuleGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block the request unless the current tenant has at least one of the given modules.
 *
 * Usage: middleware EnsureTenantModuleActive::class.':store'
 *        middleware EnsureTenantModuleActive::class.':store,pos'
 *
 * API requests receive JSON 403 with code `module_inactive`.
 * Non-API (Filament/web) requests abort 404 to hide the surface.
 */
final class EnsureTenantModuleActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $keys = [];

        foreach ($modules as $module) {
            foreach (explode(',', $module) as $key) {
                $key = trim($key);

                if ($key !== '') {
                    $keys[] = $key;
                }
            }
        }

        if ($keys !== [] && ! TenantModuleGate::anyEnabled(...$keys)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('messages.module_not_available'),
                    'error' => [
                        'code' => 'module_inactive',
                        'required_modules' => array_values(array_unique($keys)),
                    ],
                ], 403);
            }

            abort(404);
        }

        return $next($request);
    }
}
