<?php

namespace App\WhatsApp\Onboarding;

use App\Models\Tenant;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppOnboardingSession;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppTokenSource;
use App\WhatsApp\Services\WhatsAppTenantContextService;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class CompleteWhatsAppEmbeddedSignupAction
{
    public function __construct(
        protected WhatsAppEmbeddedSignupTokenExchanger $tokenExchanger,
        protected WhatsAppTenantContextService $tenantContext,
        protected FinalizeWhatsAppEmbeddedSignupAction $finalizeAction,
    ) {}

    /**
     * @param  array<string, mixed>  $sessionData  Safe Meta session payload (no secrets)
     */
    public function execute(
        WhatsAppOnboardingState $state,
        ?string $code,
        string $outcome,
        array $sessionData = [],
        ?string $clientError = null,
    ): WhatsAppOnboardingSession {
        if ($state->connectionMethod !== WhatsAppConnectionMethod::EmbeddedSignupApiOnly) {
            throw new RuntimeException('Only API Only Embedded Signup can complete this flow.');
        }

        $session = WhatsAppOnboardingSession::query()->firstOrNew(['nonce' => $state->nonce]);
        $session->fill([
            'tenant_id' => $state->tenantId,
            'user_id' => $state->userId !== null ? (string) $state->userId : null,
            'connection_method' => $state->connectionMethod->value,
            'return_url' => $state->returnUrl,
            'expires_at' => now()->addSeconds((int) config('whatsapp.embedded_signup.state_ttl_seconds', 900)),
            'waba_id' => $this->nullableString($sessionData['waba_id'] ?? null),
            'phone_number_id' => $this->nullableString($sessionData['phone_number_id'] ?? null),
            'display_phone_number' => $this->nullableString($sessionData['display_phone_number'] ?? null),
            'business_id' => $this->nullableString($sessionData['business_id'] ?? null),
            'meta_event' => $this->nullableString($sessionData['event'] ?? $sessionData['meta_event'] ?? null),
            'session_payload' => $this->sanitizeSessionPayload($sessionData),
        ]);

        if ($outcome === 'cancelled') {
            // Cancelled is terminal; use failed_at as the terminal timestamp (status remains cancelled).
            $session->status = 'cancelled';
            $session->last_error = $clientError ?: 'Embedded Signup was cancelled.';
            $session->markFailed();
            $session->save();

            return $session->fresh();
        }

        if ($outcome === 'failed') {
            $session->status = WhatsAppOnboardingStatus::Failed->value;
            $session->last_error = $clientError ?: 'Embedded Signup failed.';
            $session->markFailed();
            $session->save();

            return $session->fresh();
        }

        $tenant = Tenant::query()->find($state->tenantId);

        if ($tenant === null) {
            $session->status = WhatsAppOnboardingStatus::Failed->value;
            $session->last_error = 'Onboarding tenant was not found.';
            $session->markFailed();
            $session->save();

            return $session->fresh();
        }

        try {
            $accessToken = $this->tokenExchanger->exchange((string) $code);
        } catch (Throwable $exception) {
            $session->status = WhatsAppOnboardingStatus::Failed->value;
            $session->last_error = $exception->getMessage();
            $session->markFailed();
            $session->save();

            Log::channel(config('whatsapp.log_channel'))->warning('WhatsApp Embedded Signup completion failed during token exchange', [
                'tenant_id' => $state->tenantId,
                'nonce' => $state->nonce,
                'message' => $exception->getMessage(),
            ]);

            return $session->fresh();
        }

        $session->access_token = $accessToken;
        $session->last_error = null;
        $session->markCompleted();

        $hasPhone = filled($session->phone_number_id);
        $hasWaba = filled($session->waba_id);

        if ($hasPhone && $hasWaba) {
            $session->status = WhatsAppOnboardingStatus::SubscribingWebhooks->value;
            $numberId = $this->persistTenantNumber($tenant, $session, $accessToken);
            $session->tenant_whatsapp_number_id = $numberId;
        } elseif ($hasWaba) {
            $session->status = WhatsAppOnboardingStatus::SubscribingWebhooks->value;
        } else {
            $session->status = WhatsAppOnboardingStatus::InProgress->value;
        }

        $session->save();

        Log::channel(config('whatsapp.log_channel'))->info('WhatsApp Embedded Signup completion stored', [
            'tenant_id' => $state->tenantId,
            'nonce' => $state->nonce,
            'status' => $session->status,
            'has_waba' => $hasWaba,
            'has_phone' => $hasPhone,
            'tenant_whatsapp_number_id' => $session->tenant_whatsapp_number_id,
        ]);

        // Phase D: subscribe WABA + confirm phone metadata when a WABA is available.
        if ($hasWaba) {
            return $this->finalizeAction->execute($state);
        }

        return $session->fresh();
    }

    protected function persistTenantNumber(
        Tenant $tenant,
        WhatsAppOnboardingSession $session,
        string $accessToken,
    ): int {
        return (int) $this->tenantContext->runForTenant($tenant, function () use ($session, $accessToken) {
            $display = $session->display_phone_number
                ?: ('+'.$session->phone_number_id);

            $number = WhatsAppNumber::query()->updateOrCreate(
                ['phone_number_id' => $session->phone_number_id],
                [
                    'display_phone_number' => $display,
                    'whatsapp_business_account_id' => $session->waba_id,
                    'access_token' => $accessToken,
                    'token_type' => 'embedded_signup',
                    'token_source' => WhatsAppTokenSource::EmbeddedSignup,
                    'connection_method' => WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
                    'onboarding_status' => WhatsAppOnboardingStatus::SubscribingWebhooks,
                    'status' => WhatsAppConnectionStatus::Active,
                    'is_active' => true,
                    'last_onboarding_error' => null,
                    'connected_at' => now(),
                ],
            );

            return $number->id;
        });
    }

    /**
     * @param  array<string, mixed>  $sessionData
     * @return array<string, mixed>
     */
    protected function sanitizeSessionPayload(array $sessionData): array
    {
        $blocked = ['code', 'access_token', 'token', 'client_secret', 'app_secret'];

        return collect($sessionData)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), $blocked, true))
            ->all();
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
