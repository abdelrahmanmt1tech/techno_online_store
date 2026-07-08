<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebugTenantDatabase
{
    public function handle(Request $request, Closure $next)
    {
        dd([
            'tenant' => tenant()?->id,
            'database' => DB::connection()->getDatabaseName(),
        ]);

        return $next($request);
    }
}
