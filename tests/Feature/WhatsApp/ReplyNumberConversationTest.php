<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Actions\FindOrCreateConversationAction;

class ReplyNumberConversationTest extends WhatsAppTestCase
{
    public function test_reply_from_different_whatsapp_number_uses_separate_conversation(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $numberA = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201111111111',
                'phone_number_id' => 'phone-a',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => true,
            ]);

            $numberB = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201222222222',
                'phone_number_id' => 'phone-b',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            $action = app(FindOrCreateConversationAction::class);
            $customerPhone = '201333333333';

            $conversationA = $action->execute($numberA, $customerPhone, 'Customer');
            $conversationB = $action->execute($numberB, $customerPhone, 'Customer');

            $this->assertNotSame($conversationA->id, $conversationB->id);
            $this->assertSame($numberA->id, $conversationA->whatsapp_number_id);
            $this->assertSame($numberB->id, $conversationB->whatsapp_number_id);
            $this->assertSame(2, WhatsAppConversation::query()->count());
        });
    }
}
