<?php

namespace App\Http\Controllers;

use App\WhatsApp\Onboarding\InvalidWhatsAppOnboardingStateException;
use App\WhatsApp\Onboarding\WhatsAppOnboardingState;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppOnboardingController extends Controller
{
    public function __construct(
        protected WhatsAppOnboardingStateService $stateService,
    ) {}

    public function start(Request $request): View
    {
        $state = $this->requireValidState($request);

        return view('whatsapp.onboarding.start', [
            'state' => $state,
            'configId' => config('whatsapp.embedded_signup.config_id'),
            'metaAppId' => config('whatsapp.meta_app_id'),
            'phaseNote' => __('dashboard.whatsapp_onboarding_phase_b_note'),
        ]);
    }

    public function callback(Request $request): View
    {
        // Never trust raw tenant_id from the query/body. State must be signed.
        if ($request->filled('tenant_id') && ! $request->filled('state')) {
            abort(403, __('dashboard.whatsapp_onboarding_raw_tenant_rejected'));
        }

        $state = $this->requireValidState($request);

        return view('whatsapp.onboarding.callback', [
            'state' => $state,
            'phaseNote' => __('dashboard.whatsapp_onboarding_callback_phase_b_note'),
            // Phase B: surface query keys only (no code exchange / token storage).
            'receivedKeys' => collect($request->query())->keys()->sort()->values()->all(),
        ]);
    }

    public function status(Request $request): View
    {
        $state = $this->requireValidState($request);

        return view('whatsapp.onboarding.status', [
            'state' => $state,
            'phaseNote' => __('dashboard.whatsapp_onboarding_status_phase_b_note'),
        ]);
    }

    protected function requireValidState(Request $request): WhatsAppOnboardingState
    {
        try {
            return $this->stateService->parse((string) $request->query('state', ''));
        } catch (InvalidWhatsAppOnboardingStateException $exception) {
            abort(403, $exception->getMessage());
        }
    }
}
