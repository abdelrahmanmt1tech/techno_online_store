<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppNumberRegistry;
use App\WhatsApp\Enums\WhatsAppConnectionMethod;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use App\WhatsApp\Enums\WhatsAppTokenSource;

class WhatsAppNumberOnboardingFieldsTest extends WhatsAppTestCase
{
    public function test_manual_number_defaults_onboarding_fields_and_syncs_registry_metadata(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => 'phone-onboard-1',
                'whatsapp_business_account_id' => 'waba-onboard-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => true,
            ]);

            $this->assertSame(WhatsAppConnectionMethod::ManualApiOnly, $number->connection_method);
            $this->assertSame(WhatsAppOnboardingStatus::Completed, $number->onboarding_status);
            $this->assertSame(WhatsAppTokenSource::Manual, $number->token_source);
            $this->assertFalse($number->coexistence_enabled);
            $this->assertNull($number->business_app_number);
            $this->assertNull($number->last_onboarding_error);
            $this->assertNotNull($number->connected_at);
            $this->assertNull($number->disconnected_at);
            $this->assertNull($number->reconnect_required_at);
            $this->assertSame('********', $number->masked_access_token);
            $this->assertArrayNotHasKey('access_token', $number->toArray());

            $registry = WhatsAppNumberRegistry::query()
                ->where('phone_number_id', 'phone-onboard-1')
                ->first();

            $this->assertNotNull($registry);
            $this->assertSame(WhatsAppConnectionMethod::ManualApiOnly, $registry->connection_method);
            $this->assertSame(WhatsAppOnboardingStatus::Completed, $registry->onboarding_status);
            $this->assertFalse($registry->coexistence_enabled);
            $this->assertArrayNotHasKey('access_token', $registry->getAttributes());
        });
    }

    public function test_inactive_manual_number_can_set_disconnected_onboarding_status(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201111111111',
                'phone_number_id' => 'phone-onboard-2',
                'whatsapp_business_account_id' => 'waba-onboard-2',
                'access_token' => 'test-token',
                'status' => 'disabled',
                'is_active' => false,
                'onboarding_status' => WhatsAppOnboardingStatus::Disconnected,
                'disconnected_at' => now(),
            ]);

            $this->assertSame(WhatsAppConnectionMethod::ManualApiOnly, $number->connection_method);
            $this->assertSame(WhatsAppOnboardingStatus::Disconnected, $number->onboarding_status);
            $this->assertSame(WhatsAppTokenSource::Manual, $number->token_source);
            $this->assertNotNull($number->disconnected_at);
        });
    }
}
