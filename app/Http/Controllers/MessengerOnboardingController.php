<?php

namespace App\Http\Controllers;

use App\Messenger\Onboarding\CompleteMessengerFacebookLoginAction;
use App\Messenger\Onboarding\ConnectSelectedMessengerPagesAction;
use App\Messenger\Onboarding\InvalidMessengerOnboardingStateException;
use App\Messenger\Onboarding\MessengerOnboardingState;
use App\Messenger\Onboarding\MessengerOnboardingStateService;
use App\Messenger\Services\MessengerTenantContextService;
use App\Models\MessengerOnboardingSession;
use App\Models\Tenant;
use App\Models\Tenant\MessengerPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class MessengerOnboardingController extends Controller
{
    public function __construct(
        protected MessengerOnboardingStateService $stateService,
        protected CompleteMessengerFacebookLoginAction $completeAction,
        protected ConnectSelectedMessengerPagesAction $connectAction,
        protected MessengerTenantContextService $tenantContext,
    ) {}

    public function start(Request $request): View
    {
        $state = $this->requireValidState($request);
        $configured = $this->stateService->isConfigured();

        return view('messenger.onboarding.start', [
            'state' => $state,
            'stateToken' => (string) $request->query('state'),
            'configured' => $configured,
            'oauthUrl' => $configured
                ? $this->stateService->facebookOAuthUrl((string) $request->query('state'))
                : null,
            'configId' => config('messenger.facebook_login.config_id'),
            'metaAppId' => config('messenger.meta_app_id'),
            'scopes' => config('messenger.facebook_login.scopes'),
            'statusUrl' => route('messenger.onboarding.status', ['state' => $request->query('state')]),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->filled('tenant_id') && ! $request->filled('state')) {
            abort(403, __('dashboard.messenger_onboarding_raw_tenant_rejected'));
        }

        try {
            $state = $this->stateService->parse((string) $request->query('state', ''));
        } catch (InvalidMessengerOnboardingStateException $exception) {
            abort(403, $exception->getMessage());
        }

        if ($request->filled('error') || $request->query('error_reason') === 'user_denied') {
            $this->completeAction->cancel(
                $state,
                $this->nullableString($request->query('error_description') ?? $request->query('error')),
            );

            return redirect()->route('messenger.onboarding.status', [
                'state' => $request->query('state'),
            ]);
        }

        $code = $request->query('code');

        if (blank($code) || ! is_string($code)) {
            $this->completeAction->cancel($state, __('dashboard.messenger_onboarding_code_missing'));

            return redirect()->route('messenger.onboarding.status', [
                'state' => $request->query('state'),
            ]);
        }

        $this->completeAction->execute($state, $code);

        return redirect()->route('messenger.onboarding.pages', [
            'state' => $request->query('state'),
        ]);
    }

    public function pages(Request $request): View|RedirectResponse
    {
        $state = $this->requireValidState($request);
        $session = MessengerOnboardingSession::query()->where('nonce', $state->nonce)->first();

        if ($session === null) {
            return redirect()->route('messenger.onboarding.start', [
                'state' => $request->query('state'),
            ]);
        }

        if ($session->status === 'failed' || $session->status === 'cancelled') {
            return redirect()->route('messenger.onboarding.status', [
                'state' => $request->query('state'),
            ]);
        }

        if ($session->status === 'completed' || $session->status === 'completed_with_errors') {
            return redirect()->route('messenger.onboarding.status', [
                'state' => $request->query('state'),
            ]);
        }

        $existing = $this->existingPageIds($state);

        return view('messenger.onboarding.pages', [
            'state' => $state,
            'stateToken' => (string) $request->query('state'),
            'session' => $session,
            'pages' => $session->safePagesForUi(),
            'existingPageIds' => $existing,
            'statusUrl' => route('messenger.onboarding.status', ['state' => $request->query('state')]),
        ]);
    }

    public function connect(Request $request): RedirectResponse
    {
        if ($request->filled('tenant_id') && ! $request->filled('state')) {
            abort(403, __('dashboard.messenger_onboarding_raw_tenant_rejected'));
        }

        try {
            $state = $this->stateService->parse((string) $request->input('state', ''));
            $selected = (array) $request->input('page_ids', []);
            $this->connectAction->execute($state, $selected);
        } catch (InvalidMessengerOnboardingStateException $exception) {
            abort(403, $exception->getMessage());
        } catch (Throwable $exception) {
            return redirect()
                ->route('messenger.onboarding.pages', ['state' => $request->input('state')])
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('messenger.onboarding.status', [
            'state' => $request->input('state'),
        ]);
    }

    public function status(Request $request): View
    {
        $state = $this->requireValidState($request);
        $session = MessengerOnboardingSession::query()->where('nonce', $state->nonce)->first();

        return view('messenger.onboarding.status', [
            'state' => $state,
            'session' => $session,
            'statusLabel' => $this->statusLabel($session?->status),
            'pages' => $session?->safePagesForUi() ?? [],
            'connectedPageIds' => is_array($session?->connected_page_ids) ? $session->connected_page_ids : [],
        ]);
    }

    protected function requireValidState(Request $request): MessengerOnboardingState
    {
        try {
            return $this->stateService->parse((string) $request->query('state', $request->input('state', '')));
        } catch (InvalidMessengerOnboardingStateException $exception) {
            abort(403, $exception->getMessage());
        }
    }

    /**
     * @return list<string>
     */
    protected function existingPageIds(MessengerOnboardingState $state): array
    {
        $tenant = Tenant::query()->find($state->tenantId);

        if ($tenant === null) {
            return [];
        }

        return $this->tenantContext->runForTenant($tenant, function () {
            return MessengerPage::query()->pluck('page_id')->map(fn ($id) => (string) $id)->all();
        });
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'awaiting_page_selection' => __('dashboard.messenger_onboarding_result_awaiting_pages'),
            'subscribing_webhooks' => __('dashboard.messenger_onboarding_result_subscribing'),
            'completed' => __('dashboard.messenger_onboarding_result_completed'),
            'completed_with_errors' => __('dashboard.messenger_onboarding_result_completed_with_errors'),
            'failed' => __('dashboard.messenger_onboarding_result_failed'),
            'cancelled' => __('dashboard.messenger_onboarding_result_cancelled'),
            'in_progress' => __('dashboard.messenger_onboarding_result_in_progress'),
            default => __('dashboard.messenger_onboarding_result_not_started'),
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
