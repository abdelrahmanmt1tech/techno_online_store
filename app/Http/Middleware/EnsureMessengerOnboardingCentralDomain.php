<?php

namespace App\Http\Middleware;

use App\Messenger\Onboarding\MessengerOnboardingStateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMessengerOnboardingCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('messenger.facebook_login.enforce_central_domain', true)) {
            return $next($request);
        }

        $service = app(MessengerOnboardingStateService::class);
        $host = $request->getHttpHost();

        if (! $service->isAllowedCentralHost($host) && ! $service->isAllowedCentralHost($request->getHost())) {
            abort(403, 'Messenger onboarding is only available on the central domain.');
        }

        return $next($request);
    }
}
