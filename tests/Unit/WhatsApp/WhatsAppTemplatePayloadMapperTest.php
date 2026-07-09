<?php

namespace Tests\Unit\WhatsApp;

use App\Models\Tenant\WhatsAppTemplate;
use App\WhatsApp\Services\WhatsAppTemplateComponentBuilder;
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
        $this->assertSame(['BODY {{1}}'], $mapped['variables_schema']);
    }

    public function test_component_builder_splits_header_and_body_variables(): void
    {
        $builder = new WhatsAppTemplateComponentBuilder;

        $template = new WhatsAppTemplate([
            'components' => [
                ['type' => 'HEADER', 'text' => 'Hi {{1}}'],
                ['type' => 'BODY', 'text' => 'Order {{1}} ships on {{2}}'],
            ],
        ]);

        $this->assertSame(
            ['HEADER {{1}}', 'BODY {{1}}', 'BODY {{2}}'],
            $builder->variableSlots($template),
        );

        $components = $builder->buildApiComponents($template, ['Ahmed', '12345', 'Friday']);

        $this->assertSame([
            [
                'type' => 'header',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Ahmed'],
                ],
            ],
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => '12345'],
                    ['type' => 'text', 'text' => 'Friday'],
                ],
            ],
        ], $components);
    }
}
