<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsInitialized
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! tenant()) {
            abort(404);
        }

        return $next($request);
    }
}
