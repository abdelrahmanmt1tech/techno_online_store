<?php

namespace App\Http\Middleware;

use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWhatsAppOnboardingCentralDomain
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('whatsapp.embedded_signup.enforce_central_domain', true)) {
            return $next($request);
        }

        $service = app(WhatsAppOnboardingStateService::class);
        $host = $request->getHttpHost();

        if (! $service->isAllowedCentralHost($host) && ! $service->isAllowedCentralHost($request->getHost())) {
            abort(403, 'WhatsApp onboarding is only available on the central domain.');
        }

        return $next($request);
    }
}
