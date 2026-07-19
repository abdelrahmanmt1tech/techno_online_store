<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restrict public marketing pages (e.g. /platform) to configured central domains.
 * Does not initialize tenancy.
 */
class EnsurePublicCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.public_platform_enforce_central_domain', true)) {
            return $next($request);
        }

        $host = strtolower($request->getHttpHost() ?: $request->getHost());

        if (! $this->isAllowedCentralHost($host)) {
            abort(403, 'This page is only available on the central platform domain.');
        }

        return $next($request);
    }

    public function isAllowedCentralHost(string $host): bool
    {
        $normalized = strtolower($host);
        $withoutPort = explode(':', $normalized)[0];

        foreach ((array) config('tenancy.central_domains', []) as $domain) {
            $domain = strtolower((string) $domain);
            $domainBase = explode(':', $domain)[0];

            if ($normalized === $domain || $withoutPort === $domainBase) {
                return true;
            }

            if (str_starts_with($normalized, $domain.':') || str_starts_with($normalized, $domainBase.':')) {
                return true;
            }
        }

        return false;
    }
}
