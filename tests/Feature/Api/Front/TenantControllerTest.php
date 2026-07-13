<?php

namespace Tests\Feature\Api\Front;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');
    }

    public function test_it_creates_tenant_with_subdomain_and_plan(): void
    {
        $plan = Plan::factory()->create();

        $response = $this->postJson('/api/tenants', [
            'name' => 'Test Store',
            'email' => 'store@example.com',
            'phone' => '123456789',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'test-store',
            'plan_id' => $plan->id,
            'price' => 99.99,
            'currency' => 'SAR',
            'started_at' => now()->toDateTimeString(),
            'expires_at' => now()->addYear()->toDateTimeString(),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'phone', 'domain'],
        ]);
        $response->assertJsonPath('data.name', 'Test Store');
        $response->assertJsonPath('data.email', 'store@example.com');

        $this->assertDatabaseHas('tenants', [
            'name' => 'Test Store',
            'email' => 'store@example.com',
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/tenants', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'password', 'subdomain']);
    }

    public function test_it_validates_email_format(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'email' => 'invalid',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'my-store',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_it_validates_subdomain_format(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'INVALID_UPPERCASE',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['subdomain']);
    }

    public function test_it_validates_password_confirmation(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'different',
            'subdomain' => 'my-store',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_it_validates_plan_exists(): void
    {
        $response = $this->postJson('/api/tenants', [
            'name' => 'Store',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'subdomain' => 'my-store',
            'plan_id' => 999,
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['plan_id']);
    }
}
