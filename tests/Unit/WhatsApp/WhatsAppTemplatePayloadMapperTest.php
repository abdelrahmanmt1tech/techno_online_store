<?php

namespace Tests\Unit\WhatsApp;

use App\WhatsApp\Services\WhatsAppTemplatePayloadMapper;
use PHPUnit\Framework\TestCase;

class WhatsAppTemplatePayloadMapperTest extends TestCase
{
    public function test_it_maps_meta_payload_and_variables(): void
    {
        $mapper = new WhatsAppTemplatePayloadMapper;

        $mapped = $mapper->mapFromMeta([
            'id' => '123',
            'name' => 'hello_world',
            'language' => 'en_US',
            'status' => 'APPROVED',
            'category' => 'MARKETING',
            'components' => [
                ['type' => 'BODY', 'text' => 'Hello {{1}}'],
            ],
        ]);

        $this->assertSame('123', $mapped['provider_template_id']);
        $this->assertSame('hello_world', $mapped['name']);
        $this->assertSame('approved', $mapped['status']->value);
        $this->assertSame('marketing', $mapped['category']->value);
        $this->assertSame(['{{1}}'], $mapped['variables_schema']);
    }
}
