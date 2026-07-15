<?php

namespace Tests\Feature\Api\Front;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_contact_message(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'message' => 'Hello, I have a question.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('contacts', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '123456789',
            'message' => 'Hello, I have a question.',
        ]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->postJson('/api/contact', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'email', 'phone', 'message']);
    }

    public function test_it_validates_email_format(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'John',
            'email' => 'invalid',
            'phone' => '123',
            'message' => 'Test',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }
}
