<?php

namespace Tests\Unit\MessagingHealth;

use App\Models\WhatsAppNumberRegistry;
use App\Support\MessagingHealth\MessagingHealthStatus;
use App\Support\MessagingHealth\WhatsAppRegistryHealthEvaluator;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppOnboardingStatus;
use PHPUnit\Framework\TestCase;

class WhatsAppRegistryHealthEvaluatorTest extends TestCase
{
    protected WhatsAppRegistryHealthEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new WhatsAppRegistryHealthEvaluator;
    }

    public function test_active_subscribed_is_healthy(): void
    {
        $row = $this->registry([
            'status' => WhatsAppConnectionStatus::Active,
            'is_active' => true,
            'webhook_status' => 'subscribed',
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
        ]);

        $this->assertSame(MessagingHealthStatus::Healthy, $this->evaluator->evaluate($row));
    }

    public function test_active_without_subscription_is_warning(): void
    {
        $row = $this->registry([
            'status' => WhatsAppConnectionStatus::Active,
            'is_active' => true,
            'webhook_status' => 'pending',
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
        ]);

        $this->assertSame(MessagingHealthStatus::Warning, $this->evaluator->evaluate($row));
    }

    public function test_reconnect_required(): void
    {
        $row = $this->registry([
            'status' => WhatsAppConnectionStatus::ReconnectRequired,
            'is_active' => true,
            'webhook_status' => 'subscribed',
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
        ]);

        $this->assertSame(MessagingHealthStatus::ReconnectRequired, $this->evaluator->evaluate($row));
    }

    public function test_failed(): void
    {
        $row = $this->registry([
            'status' => WhatsAppConnectionStatus::Failed,
            'is_active' => true,
            'webhook_status' => 'failed',
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
        ]);

        $this->assertSame(MessagingHealthStatus::Failed, $this->evaluator->evaluate($row));
    }

    public function test_inactive_is_disabled(): void
    {
        $row = $this->registry([
            'status' => WhatsAppConnectionStatus::Active,
            'is_active' => false,
            'webhook_status' => 'subscribed',
            'onboarding_status' => WhatsAppOnboardingStatus::Completed,
        ]);

        $this->assertSame(MessagingHealthStatus::Disabled, $this->evaluator->evaluate($row));
    }

    public function test_onboarding_in_progress_is_pending(): void
    {
        $row = $this->registry([
            'status' => WhatsAppConnectionStatus::Active,
            'is_active' => true,
            'webhook_status' => 'pending',
            'onboarding_status' => WhatsAppOnboardingStatus::InProgress,
        ]);

        $this->assertSame(MessagingHealthStatus::Pending, $this->evaluator->evaluate($row));
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function registry(array $attrs): WhatsAppNumberRegistry
    {
        $model = new WhatsAppNumberRegistry;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }
}
