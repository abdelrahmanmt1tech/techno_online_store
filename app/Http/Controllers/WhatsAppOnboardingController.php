<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppOnboardingSession;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Onboarding\CompleteWhatsAppEmbeddedSignupAction;
use App\WhatsApp\Onboarding\FinalizeWhatsAppEmbeddedSignupAction;
use App\WhatsApp\Onboarding\InvalidWhatsAppOnboardingStateException;
use App\WhatsApp\Onboarding\WhatsAppOnboardingState;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WhatsAppOnboardingController extends Controller
{
    public function __construct(
        protected WhatsAppOnboardingStateService $stateService,
        protected CompleteWhatsAppEmbeddedSignupAction $completeAction,
        protected FinalizeWhatsAppEmbeddedSignupAction $finalizeAction,
    ) {}

    public function start(Request $request): View
    {
        $state = $this->requireValidState($request);
        $this->requireEmbeddedSignup($state);

        $metaAppId = config('whatsapp.meta_app_id');
        $configId = $this->configIdFor($state);
        $canLaunch = filled($metaAppId) && filled($configId);
        $isCoexistence = $state->connectionMethod->isCoexistence();

        return view('whatsapp.onboarding.start', [
            'state' => $state,
            'stateToken' => (string) $request->query('state'),
            'configId' => $configId,
            'metaAppId' => $metaAppId,
            'graphVersion' => config('whatsapp.graph_api_version', 'v21.0'),
            'canLaunch' => $canLaunch,
            'isCoexistence' => $isCoexistence,
            'completeUrl' => route('whatsapp.onboarding.complete'),
            'statusUrl' => route('whatsapp.onboarding.status', ['state' => $request->query('state')]),
            'phaseNote' => $isCoexistence
                ? __('dashboard.whatsapp_onboarding_phase_e_note')
                : __('dashboard.whatsapp_onboarding_phase_c_note'),
            'missingConfigMessage' => $isCoexistence
                ? __('dashboard.whatsapp_onboarding_missing_coexistence_config')
                : __('dashboard.whatsapp_onboarding_missing_meta_config'),
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
            'phaseNote' => $state->connectionMethod->isCoexistence()
                ? __('dashboard.whatsapp_onboarding_callback_phase_e_note')
                : __('dashboard.whatsapp_onboarding_callback_phase_c_note'),
            'receivedKeys' => collect($request->query())->keys()->sort()->values()->all(),
        ]);
    }

    public function status(Request $request): View
    {
        $state = $this->requireValidState($request);
        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();
        $status = $session?->status;
        $isCoexistence = $state->connectionMethod->isCoexistence();

        $canRetryFinalize = $session !== null && in_array($status, [
            WhatsAppOnboardingStatus::SubscribingWebhooks->value,
            WhatsAppOnboardingStatus::Failed->value,
            WhatsAppOnboardingStatus::AwaitingPhoneSelection->value,
            WhatsAppOnboardingStatus::InProgress->value,
        ], true);

        return view('whatsapp.onboarding.status', [
            'state' => $state,
            'session' => $session,
            'statusLabel' => $this->statusLabel($status, $isCoexistence),
            'phaseNote' => $isCoexistence
                ? __('dashboard.whatsapp_onboarding_status_phase_e_note')
                : __('dashboard.whatsapp_onboarding_status_phase_d_note'),
            'canRetryFinalize' => $canRetryFinalize,
            'isSuccess' => $status === WhatsAppOnboardingStatus::Completed->value,
            'isCoexistence' => $isCoexistence,
            'availablePhones' => is_array($session?->session_payload['available_phones'] ?? null)
                ? $session->session_payload['available_phones']
                : [],
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
            $this->requireEmbeddedSignup($state);
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

    public function finalize(Request $request): RedirectResponse
    {
        if ($request->filled('tenant_id') && ! $request->filled('state')) {
            abort(403, __('dashboard.whatsapp_onboarding_raw_tenant_rejected'));
        }

        try {
            $state = $this->stateService->parse((string) $request->input('state', ''));
            $this->requireEmbeddedSignup($state);
            $this->finalizeAction->execute($state);
        } catch (InvalidWhatsAppOnboardingStateException $exception) {
            abort(403, $exception->getMessage());
        } catch (Throwable $exception) {
            return redirect()
                ->route('whatsapp.onboarding.status', ['state' => $request->input('state')])
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('whatsapp.onboarding.status', [
            'state' => $request->input('state'),
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

    protected function requireEmbeddedSignup(WhatsAppOnboardingState $state): void
    {
        if (! $state->connectionMethod->isEmbeddedSignup()) {
            abort(403, __('dashboard.whatsapp_onboarding_embedded_signup_required'));
        }
    }

    protected function configIdFor(WhatsAppOnboardingState $state): ?string
    {
        if ($state->connectionMethod->isCoexistence()) {
            $id = config('whatsapp.embedded_signup.coexistence_config_id');

            return filled($id) ? (string) $id : null;
        }

        $id = config('whatsapp.embedded_signup.config_id');

        return filled($id) ? (string) $id : null;
    }

    protected function statusLabel(?string $status, bool $isCoexistence = false): string
    {
        return match ($status) {
            'subscribing_webhooks' => __('dashboard.whatsapp_onboarding_result_subscribing_webhooks'),
            'awaiting_phone_selection' => __('dashboard.whatsapp_onboarding_result_awaiting_phone'),
            'in_progress' => $isCoexistence
                ? __('dashboard.whatsapp_onboarding_result_coexistence_pending_validation')
                : __('dashboard.whatsapp_onboarding_result_in_progress'),
            'failed' => __('dashboard.whatsapp_onboarding_result_failed'),
            'cancelled' => __('dashboard.whatsapp_onboarding_result_cancelled'),
            'completed' => $isCoexistence
                ? __('dashboard.whatsapp_onboarding_result_coexistence_completed')
                : __('dashboard.whatsapp_onboarding_result_completed'),
            'reconnect_required' => __('dashboard.whatsapp_onboarding_result_reconnect_required'),
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
