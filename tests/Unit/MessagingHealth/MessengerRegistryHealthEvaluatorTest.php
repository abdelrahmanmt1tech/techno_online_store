<?php

namespace Tests\Unit\MessagingHealth;

use App\Messenger\Enums\MessengerPageStatus;
use App\Models\MessengerPageRegistry;
use App\Support\MessagingHealth\MessagingHealthStatus;
use App\Support\MessagingHealth\MessengerRegistryHealthEvaluator;
use PHPUnit\Framework\TestCase;

class MessengerRegistryHealthEvaluatorTest extends TestCase
{
    protected MessengerRegistryHealthEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = new MessengerRegistryHealthEvaluator;
    }

    public function test_active_subscribed_is_healthy(): void
    {
        $row = $this->registry([
            'status' => MessengerPageStatus::Active,
            'is_active' => true,
            'webhook_status' => 'subscribed',
        ]);

        $this->assertSame(MessagingHealthStatus::Healthy, $this->evaluator->evaluate($row));
    }

    public function test_active_without_subscription_is_warning(): void
    {
        $row = $this->registry([
            'status' => MessengerPageStatus::Active,
            'is_active' => true,
            'webhook_status' => 'pending',
        ]);

        $this->assertSame(MessagingHealthStatus::Warning, $this->evaluator->evaluate($row));
    }

    public function test_reconnect_required(): void
    {
        $row = $this->registry([
            'status' => MessengerPageStatus::ReconnectRequired,
            'is_active' => true,
            'webhook_status' => 'subscribed',
        ]);

        $this->assertSame(MessagingHealthStatus::ReconnectRequired, $this->evaluator->evaluate($row));
    }

    public function test_failed(): void
    {
        $row = $this->registry([
            'status' => MessengerPageStatus::Failed,
            'is_active' => true,
            'webhook_status' => 'failed',
        ]);

        $this->assertSame(MessagingHealthStatus::Failed, $this->evaluator->evaluate($row));
    }

    public function test_inactive_is_disabled(): void
    {
        $row = $this->registry([
            'status' => MessengerPageStatus::Disabled,
            'is_active' => false,
            'webhook_status' => 'subscribed',
        ]);

        $this->assertSame(MessagingHealthStatus::Disabled, $this->evaluator->evaluate($row));
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    protected function registry(array $attrs): MessengerPageRegistry
    {
        $model = new MessengerPageRegistry;
        foreach ($attrs as $key => $value) {
            $model->{$key} = $value;
        }

        return $model;
    }
}
