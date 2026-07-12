<?php

namespace Tests\Feature\WhatsApp;

use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Onboarding\InvalidWhatsAppOnboardingStateException;
use App\WhatsApp\Onboarding\WhatsAppOnboardingStateService;
use Illuminate\Support\Facades\Crypt;

class WhatsAppOnboardingStateTest extends WhatsAppTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.embedded_signup.state_ttl_seconds' => 900,
            'whatsapp.embedded_signup.central_domain' => 'online-store.technomasrsystems.com',
        ]);
    }

    public function test_issued_state_round_trips_tenant_and_method(): void
    {
        $service = app(WhatsAppOnboardingStateService::class);

        $token = $service->issue(
            tenantId: 'tenant-abc',
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'https://store1.technomasrsystems.com/app/whatsapp-numbers',
            userId: 42,
        );

        $state = $service->parse($token);

        $this->assertSame('tenant-abc', $state->tenantId);
        $this->assertSame(WhatsAppConnectionMethod::EmbeddedSignupApiOnly, $state->connectionMethod);
        $this->assertSame(42, $state->userId);
        $this->assertSame('https://store1.technomasrsystems.com/app/whatsapp-numbers', $state->returnUrl);
        $this->assertNotSame('', $state->nonce);
        $this->assertFalse($state->isExpired());
    }

    public function test_tampered_state_is_rejected(): void
    {
        $service = app(WhatsAppOnboardingStateService::class);

        $token = $service->issue(
            tenantId: 'tenant-abc',
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'https://store1.technomasrsystems.com/app/whatsapp-numbers',
        );

        $this->expectException(InvalidWhatsAppOnboardingStateException::class);

        $service->parse($token.'tampered');
    }

    public function test_expired_state_is_rejected(): void
    {
        $service = app(WhatsAppOnboardingStateService::class);

        $token = $service->issue(
            tenantId: 'tenant-abc',
            connectionMethod: WhatsAppConnectionMethod::EmbeddedSignupApiOnly,
            returnUrl: 'https://store1.technomasrsystems.com/app/whatsapp-numbers',
            ttlSeconds: 60,
        );

        $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        $payload['issued_at'] = time() - 120;
        $payload['expires_at'] = time() - 60;
        $expiredToken = Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->expectException(InvalidWhatsAppOnboardingStateException::class);
        $this->expectExceptionMessage('expired');

        $service->parse($expiredToken);
    }

    public function test_central_url_uses_configured_central_domain(): void
    {
        $service = app(WhatsAppOnboardingStateService::class);

        $url = $service->centralUrl('start', 'token-value');

        $this->assertStringContainsString('://online-store.technomasrsystems.com/whatsapp/onboarding/start', $url);
        $this->assertStringContainsString('state=token-value', $url);
    }
}
