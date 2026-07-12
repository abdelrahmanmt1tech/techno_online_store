<?php

namespace Tests\Unit\WhatsApp;

use App\WhatsApp\Services\WhatsAppWebhookInterpreter;
use Tests\TestCase;

class WhatsAppWebhookInterpreterTest extends TestCase
{
    public function test_it_interprets_inbound_text_message(): void
    {
        $interpreter = new WhatsAppWebhookInterpreter;

        $result = $interpreter->interpret([
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => '123'],
                        'contacts' => [['profile' => ['name' => 'Ahmed']]],
                        'messages' => [[
                            'id' => 'wamid.1',
                            'from' => '201006960579',
                            'type' => 'text',
                            'text' => ['body' => 'Hello'],
                            'timestamp' => '1710000000',
                        ]],
                    ],
                ]],
            ]],
        ], 'messages');

        $this->assertSame('inbound_message', $result['kind']);
        $this->assertStringContainsString('201006960579', $result['summary']);
        $this->assertContains('Hello', array_values($result['details'][0]['items']));
    }

    public function test_it_interprets_invalid_signature_event(): void
    {
        $interpreter = new WhatsAppWebhookInterpreter;

        $result = $interpreter->interpret(null, 'invalid_signature', false);

        $this->assertSame('security', $result['kind']);
        $this->assertNotSame('', $result['summary']);
    }
}
