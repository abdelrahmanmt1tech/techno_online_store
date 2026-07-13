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

class FinalizeWhatsAppEmbeddedSignupAction
{
    public function __construct(
        protected SubscribeWhatsAppWabaWebhooksAction $subscribeAction,
        protected ConfirmWhatsAppPhoneMetadataAction $phoneMetadataAction,
        protected WhatsAppTenantContextService $tenantContext,
    ) {}

    /**
     * Complete Phase D: subscribe WABA webhooks, confirm phone metadata, activate tenant number.
     * Idempotent for already-completed sessions. Safe to retry after failure.
     */
    public function execute(WhatsAppOnboardingState $state): WhatsAppOnboardingSession
    {
        if ($state->connectionMethod !== WhatsAppConnectionMethod::EmbeddedSignupApiOnly) {
            throw new RuntimeException('Only API Only Embedded Signup can finalize this flow.');
        }

        $session = WhatsAppOnboardingSession::query()->where('nonce', $state->nonce)->first();

        if ($session === null) {
            throw new RuntimeException('Onboarding session was not found for this signed state.');
        }

        if ($session->tenant_id !== $state->tenantId) {
            throw new RuntimeException('Onboarding session tenant does not match signed state.');
        }

        if ($session->status === 'cancelled') {
            throw new RuntimeException('Cancelled onboarding sessions cannot be finalized.');
        }

        if ($session->status === WhatsAppOnboardingStatus::Completed->value) {
            return $session;
        }

        $tenant = Tenant::query()->find($state->tenantId);

        if ($tenant === null) {
            $this->failSession($session, 'Onboarding tenant was not found.');

            return $session->fresh();
        }

        $accessToken = $this->resolveAccessToken($session, $tenant);

        if (blank($accessToken)) {
            $this->failSession($session, 'No access token available to finalize onboarding. Restart Embedded Signup.');

            return $session->fresh();
        }

        if (blank($session->waba_id)) {
            $this->failSession($session, 'WABA id is missing; cannot subscribe webhooks.');

            return $session->fresh();
        }

        $session->status = WhatsAppOnboardingStatus::SubscribingWebhooks->value;
        $session->last_error = null;
        $session->failed_at = null;
        $session->save();

        try {
            $result = $this->tenantContext->runForTenant($tenant, function () use ($session, $accessToken) {
                return $this->runPhaseDInsideTenant($session, $accessToken);
            });

            $session->refresh();

            if ($result['outcome'] === 'awaiting_phone_selection') {
                $session->status = WhatsAppOnboardingStatus::AwaitingPhoneSelection->value;
                $session->last_error = $result['error'];
                $session->session_payload = $this->mergePayload($session, [
                    'subscribed_apps' => $result['subscription'],
                    'available_phones' => $result['available_phones'],
                    'phase_d' => 'awaiting_phone_selection',
                ]);
                $session->save();

                Log::channel(config('whatsapp.log_channel'))->info('WhatsApp onboarding awaiting phone selection', [
                    'tenant_id' => $session->tenant_id,
                    'nonce' => $session->nonce,
                    'available_count' => count($result['available_phones']),
                ]);

                return $session->fresh();
            }

            $session->phone_number_id = $result['phone_number_id'];
            $session->display_phone_number = $result['display_phone_number'] ?: $session->display_phone_number;
            $session->tenant_whatsapp_number_id = $result['number_id'];
            $session->status = WhatsAppOnboardingStatus::Completed->value;
            $session->last_error = null;
            $session->session_payload = $this->mergePayload($session, [
                'subscribed_apps' => $result['subscription'],
                'phase_d' => 'completed',
                'available_phones' => $result['available_phones'],
            ]);
            $session->markCompleted();
            $session->save();

            Log::channel(config('whatsapp.log_channel'))->info('WhatsApp Embedded Signup Phase D completed', [
                'tenant_id' => $session->tenant_id,
                'nonce' => $session->nonce,
                'waba_id' => $session->waba_id,
                'phone_number_id' => $session->phone_number_id,
                'tenant_whatsapp_number_id' => $session->tenant_whatsapp_number_id,
            ]);

            return $session->fresh();
        } catch (Throwable $exception) {
            $this->failSession($session, $exception->getMessage(), $tenant, $accessToken);

            Log::channel(config('whatsapp.log_channel'))->warning('WhatsApp Embedded Signup Phase D failed', [
                'tenant_id' => $session->tenant_id,
                'nonce' => $session->nonce,
                'message' => $exception->getMessage(),
            ]);

            return $session->fresh();
        } finally {
            $this->tenantContext->end();
        }
    }

    /**
     * @return array{
     *     outcome: string,
     *     subscription: array{success: bool, http_status: int, success_flag: bool|null, message: string|null},
     *     phone_number_id: ?string,
     *     display_phone_number: ?string,
     *     number_id: ?int,
     *     available_phones: list<array{id: string, display_phone_number: ?string, verified_name: ?string}>,
     *     error: ?string
     * }
     */
    protected function runPhaseDInsideTenant(WhatsAppOnboardingSession $session, string $accessToken): array
    {
        $existing = $this->findNumberInCurrentTenant($session);

        $subscription = $existing !== null
            ? $this->subscribeAction->execute($existing)
            : $this->subscribeAction->executeWithToken($accessToken, (string) $session->waba_id);

        $phoneResult = $this->phoneMetadataAction->execute(
            accessToken: $accessToken,
            wabaId: (string) $session->waba_id,
            preferredPhoneNumberId: $session->phone_number_id,
            numberForLogging: $existing,
        );

        if ($phoneResult->needsPhoneSelection()) {
            return [
                'outcome' => 'awaiting_phone_selection',
                'subscription' => $subscription,
                'phone_number_id' => null,
                'display_phone_number' => null,
                'number_id' => null,
                'available_phones' => $phoneResult->availablePhones,
                'error' => $phoneResult->error,
            ];
        }

        if (! $phoneResult->isConfirmed() || blank($phoneResult->phoneNumberId)) {
            throw new RuntimeException($phoneResult->error ?: 'Phone number metadata could not be confirmed.');
        }

        $number = $this->upsertTenantNumber($session, $accessToken, $phoneResult);

        $number->forceFill([
            'webhook_status' => 'subscribed',
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
            'status' => WhatsAppConnectionStatus::Active,
            'is_active' => true,
            'last_onboarding_error' => null,
            'connected_at' => $number->connected_at ?? now(),
            'business_name' => $phoneResult->verifiedName ?: $number->business_name,
            'display_phone_number' => $phoneResult->displayPhoneNumber
                ?: $number->display_phone_number,
        ])->save();

        return [
            'outcome' => 'completed',
            'subscription' => $subscription,
            'phone_number_id' => $phoneResult->phoneNumberId,
            'display_phone_number' => $phoneResult->displayPhoneNumber,
            'number_id' => $number->id,
            'available_phones' => $phoneResult->availablePhones,
            'error' => null,
        ];
    }

    protected function findNumberInCurrentTenant(WhatsAppOnboardingSession $session): ?WhatsAppNumber
    {
        if ($session->tenant_whatsapp_number_id !== null) {
            return WhatsAppNumber::query()->find($session->tenant_whatsapp_number_id);
        }

        if (filled($session->phone_number_id)) {
            return WhatsAppNumber::query()
                ->where('phone_number_id', $session->phone_number_id)
                ->first();
        }

        return null;
    }

    protected function resolveAccessToken(WhatsAppOnboardingSession $session, Tenant $tenant): ?string
    {
        if (filled($session->access_token)) {
            return (string) $session->access_token;
        }

        return $this->tenantContext->runForTenant($tenant, function () use ($session) {
            return $this->findNumberInCurrentTenant($session)?->access_token;
        });
    }

    protected function upsertTenantNumber(
        WhatsAppOnboardingSession $session,
        string $accessToken,
        WhatsAppPhoneMetadataResult $phoneResult,
    ): WhatsAppNumber {
        $display = $phoneResult->displayPhoneNumber
            ?: $session->display_phone_number
            ?: ('+'.$phoneResult->phoneNumberId);

        return WhatsAppNumber::query()->updateOrCreate(
            ['phone_number_id' => $phoneResult->phoneNumberId],
            [
                'display_phone_number' => $display,
                'whatsapp_business_account_id' => $session->waba_id,
                'business_name' => $phoneResult->verifiedName,
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
    }

    protected function failSession(
        WhatsAppOnboardingSession $session,
        string $message,
        ?Tenant $tenant = null,
        ?string $accessToken = null,
    ): void {
        $session->status = WhatsAppOnboardingStatus::Failed->value;
        $session->last_error = $message;
        $session->session_payload = $this->mergePayload($session, [
            'phase_d' => 'failed',
        ]);
        $session->markFailed();
        $session->save();

        if ($tenant === null) {
            return;
        }

        $this->tenantContext->runForTenant($tenant, function () use ($session, $message, $accessToken) {
            $number = $this->findNumberInCurrentTenant($session);

            if ($number === null) {
                return;
            }

            $number->forceFill([
                'onboarding_status' => WhatsAppOnboardingStatus::Failed,
                'last_onboarding_error' => $message,
                'access_token' => filled($accessToken) ? $accessToken : $number->access_token,
            ])->save();
        });
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function mergePayload(WhatsAppOnboardingSession $session, array $extra): array
    {
        $payload = is_array($session->session_payload) ? $session->session_payload : [];

        return array_merge($payload, $extra);
    }
}
