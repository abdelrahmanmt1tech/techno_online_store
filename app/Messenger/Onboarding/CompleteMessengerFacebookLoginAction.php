<?php

namespace App\Messenger\Onboarding;

use App\Messenger\Services\MessengerGraphApiService;
use App\Models\MessengerOnboardingSession;
use Illuminate\Support\Facades\Log;
use Throwable;

class CompleteMessengerFacebookLoginAction
{
    public function __construct(
        protected MessengerFacebookLoginTokenExchanger $tokenExchanger,
        protected MessengerGraphApiService $graphApi,
    ) {}

    public function execute(MessengerOnboardingState $state, string $code): MessengerOnboardingSession
    {
        $session = MessengerOnboardingSession::query()->firstOrNew(['nonce' => $state->nonce]);
        $session->fill([
            'tenant_id' => $state->tenantId,
            'user_id' => $state->userId !== null ? (string) $state->userId : null,
            'return_url' => $state->returnUrl,
            'expires_at' => now()->addSeconds((int) config('messenger.facebook_login.state_ttl_seconds', 900)),
            'status' => 'in_progress',
            'last_error' => null,
            'failed_at' => null,
        ]);

        try {
            $userToken = $this->tokenExchanger->exchangeCode($code);
            $pages = $this->graphApi->listManagedPages($userToken);
        } catch (Throwable $exception) {
            $session->status = 'failed';
            $session->last_error = $exception->getMessage();
            $session->markFailed();
            $session->save();

            Log::channel(config('messenger.log_channel'))->warning('Messenger Facebook Login completion failed', [
                'tenant_id' => $state->tenantId,
                'nonce' => $state->nonce,
                'message' => $exception->getMessage(),
            ]);

            return $session->fresh();
        }

        if ($pages === []) {
            $session->status = 'failed';
            $session->last_error = 'No manageable Facebook Pages were returned for this account.';
            $session->markFailed();
            $session->save();

            return $session->fresh();
        }

        // Keep page tokens encrypted in session for the picker step only; clear user token.
        $session->user_access_token = null;
        $session->pages_payload = $pages;
        $session->status = 'awaiting_page_selection';
        $session->last_error = null;
        $session->save();

        Log::channel(config('messenger.log_channel'))->info('Messenger Facebook Login pages ready for selection', [
            'tenant_id' => $state->tenantId,
            'nonce' => $state->nonce,
            'page_count' => count($pages),
        ]);

        return $session->fresh();
    }

    public function cancel(MessengerOnboardingState $state, ?string $error = null): MessengerOnboardingSession
    {
        $session = MessengerOnboardingSession::query()->firstOrNew(['nonce' => $state->nonce]);
        $session->fill([
            'tenant_id' => $state->tenantId,
            'user_id' => $state->userId !== null ? (string) $state->userId : null,
            'return_url' => $state->returnUrl,
            'expires_at' => now()->addSeconds((int) config('messenger.facebook_login.state_ttl_seconds', 900)),
            'status' => 'cancelled',
            'last_error' => $error ?: 'Messenger Facebook Login was cancelled.',
        ]);
        $session->markFailed();
        $session->save();

        return $session->fresh();
    }
}
