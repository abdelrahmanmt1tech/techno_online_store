<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppOnboardingSession;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Onboarding\CompleteWhatsAppEmbeddedSignupAction;
use App\WhatsApp\Onboarding\InvalidWhatsAppOnboardingStateException;
use App\WhatsApp\Onboarding\WhatsAppOnboardingState;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WhatsAppOnboardingController extends Controller
{
    public function __construct(
        protected WhatsAppOnboardingStateService $stateService,
        protected CompleteWhatsAppEmbeddedSignupAction $completeAction,
    ) {}

    public function start(Request $request): View
    {
        $state = $this->requireValidState($request);
        $this->requireApiOnly($state);

        $metaAppId = config('whatsapp.meta_app_id');
        $configId = config('whatsapp.embedded_signup.config_id');
        $canLaunch = filled($metaAppId) && filled($configId);

        return view('whatsapp.onboarding.start', [
            'state' => $state,
            'stateToken' => (string) $request->query('state'),
            'configId' => $configId,
            'metaAppId' => $metaAppId,
            'graphVersion' => config('whatsapp.graph_api_version', 'v21.0'),
            'canLaunch' => $canLaunch,
            'completeUrl' => route('whatsapp.onboarding.complete'),
            'statusUrl' => route('whatsapp.onboarding.status', ['state' => $request->query('state')]),
            'phaseNote' => __('dashboard.whatsapp_onboarding_phase_c_note'),
        ]);
    }

    public function callback(Request $request): View
    {
        if ($request->filled('tenant_id') && ! $request->filled('state')) {
            abort(403, __('dashboard.whatsapp_onboarding_raw_tenant_rejected'));
        }

        $state = $this->requireValidState($request);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();

        return view('whatsapp.onboarding.callback', [
            'state' => $state,
            'session' => $session,
            'phaseNote' => __('dashboard.whatsapp_onboarding_callback_phase_c_note'),
            'receivedKeys' => collect($request->query())->keys()->sort()->values()->all(),
        ]);
    }

    public function status(Request $request): View
    {
        $state = $this->requireValidState($request);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();

        return view('whatsapp.onboarding.status', [
            'state' => $state,
            'session' => $session,
            'statusLabel' => $this->statusLabel($session?->status),
            'phaseNote' => __('dashboard.whatsapp_onboarding_status_phase_c_note'),
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        if ($request->filled('tenant_id') && ! $request->filled('state')) {
            return response()->json([
                'ok' => false,
                'message' => __('dashboard.whatsapp_onboarding_raw_tenant_rejected'),
            ], 403);
        }

        try {
            $state = $this->stateService->parse((string) $request->input('state', ''));
            $this->requireApiOnly($state);
        } catch (InvalidWhatsAppOnboardingStateException $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 403);
        } catch (Throwable $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $outcome = (string) $request->input('outcome', 'success');
        $code = $request->input('code');
        $sessionData = (array) $request->input('session', []);

        if ($outcome === 'success' && blank($code)) {
            return response()->json([
                'ok' => false,
                'message' => __('dashboard.whatsapp_onboarding_code_missing'),
            ], 422);
        }

        try {
            $session = $this->completeAction->execute(
                state: $state,
                code: is_string($code) ? $code : null,
                outcome: $outcome,
                sessionData: $sessionData,
                clientError: $this->nullableString($request->input('error')),
            );
        } catch (Throwable $exception) {
            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $statusUrl = route('whatsapp.onboarding.status', [
            'state' => $request->input('state'),
        ]);

        return response()->json([
            'ok' => $session->status !== 'failed' && $session->status !== 'cancelled',
            'status' => $session->status,
            'redirect' => $statusUrl,
            'message' => $session->last_error,
        ]);
    }

    protected function requireValidState(Request $request): WhatsAppOnboardingState
    {
        try {
            return $this->stateService->parse((string) $request->query('state', $request->input('state', '')));
        } catch (InvalidWhatsAppOnboardingStateException $exception) {
            abort(403, $exception->getMessage());
        }
    }

    protected function requireApiOnly(WhatsAppOnboardingState $state): void
    {
        if ($state->connectionMethod !== WhatsAppConnectionMethod::EmbeddedSignupApiOnly) {
            abort(403, __('dashboard.whatsapp_onboarding_api_only_required'));
        }
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'subscribing_webhooks' => __('dashboard.whatsapp_onboarding_result_success_pending_phase_d'),
            'awaiting_phone_selection' => __('dashboard.whatsapp_onboarding_result_awaiting_phone'),
            'in_progress' => __('dashboard.whatsapp_onboarding_result_in_progress'),
            'failed' => __('dashboard.whatsapp_onboarding_result_failed'),
            'cancelled' => __('dashboard.whatsapp_onboarding_result_cancelled'),
            'completed' => __('dashboard.whatsapp_onboarding_result_completed'),
            default => __('dashboard.whatsapp_onboarding_result_not_started'),
        };
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
