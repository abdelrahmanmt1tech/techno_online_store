<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\Models\TenantUser;
use App\WhatsApp\Actions\OpenCustomerServiceWindowAction;
use App\WhatsApp\Actions\SendWhatsAppTemplateMessageAction;
use App\WhatsApp\DTOs\SendTemplateMessageData;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use App\WhatsApp\Services\WhatsAppSendingPolicyService;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

class CustomerServiceWindowTest extends WhatsAppTestCase
{
    public function test_inbound_message_opens_24h_window(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            $conversation = WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
            ]);

            app(OpenCustomerServiceWindowAction::class)->execute($conversation, now());

            $conversation->refresh();
            $this->assertTrue($conversation->canSendFreeformReply());
        });
    }

    public function test_normal_text_is_blocked_outside_window(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'agent@example.com',
                'password' => 'password',
            ]);

            Permission::create(['name' => 'whatsapp.send_messages', 'guard_name' => 'tenant']);
            $user->givePermissionTo('whatsapp.send_messages');

            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            $conversation = WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->subMinute(),
            ]);

            $result = app(WhatsAppSendingPolicyService::class)->canSendText($user, $number, $conversation);

            $this->assertFalse($result->allowed);
            $this->assertTrue($result->mustUseTemplate);
        });
    }

    public function test_approved_template_can_be_sent_outside_window(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.TEMPLATE1']]], 200)]);

        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Agent',
                'email' => 'agent@example.com',
                'password' => 'password',
            ]);

            Permission::create(['name' => 'whatsapp.send_template_messages', 'guard_name' => 'tenant']);
            $user->givePermissionTo('whatsapp.send_template_messages');

            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => '123456789',
                'whatsapp_business_account_id' => 'waba-1',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_active' => true,
                'is_default' => false,
            ]);

            $conversation = WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->subHour(),
            ]);

            $template = WhatsAppTemplate::query()->create([
                'whatsapp_business_account_id' => 'waba-1',
                'name' => 'hello_world',
                'language' => 'en',
                'category' => 'utility',
                'status' => WhatsAppTemplateStatus::Approved,
            ]);

            $message = app(SendWhatsAppTemplateMessageAction::class)->execute(
                new SendTemplateMessageData($number, $conversation, $template),
                $user,
            );

            $this->assertSame('wamid.TEMPLATE1', $message->provider_message_id);
        });
    }
}
