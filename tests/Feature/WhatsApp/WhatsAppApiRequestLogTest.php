<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppApiRequest;
use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\Models\TenantUser;
use App\WhatsApp\Actions\SendWhatsAppTemplateMessageAction;
use App\WhatsApp\DTOs\SendTemplateMessageData;
use App\WhatsApp\Enums\WhatsAppApiRequestOperation;
use App\WhatsApp\Enums\WhatsAppApiRequestOutcome;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;

class WhatsAppApiRequestLogTest extends WhatsAppTestCase
{
    public function test_outbound_template_send_creates_api_request_log(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.LOG1']]], 200)]);

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
            ]);

            $template = WhatsAppTemplate::query()->create([
                'whatsapp_business_account_id' => 'waba-1',
                'name' => 'hello_world',
                'language' => 'en',
                'category' => 'utility',
                'status' => WhatsAppTemplateStatus::Approved,
            ]);

            app(SendWhatsAppTemplateMessageAction::class)->execute(
                new SendTemplateMessageData($number, $conversation, $template),
                $user,
            );

            $log = WhatsAppApiRequest::query()->first();

            $this->assertNotNull($log);
            $this->assertSame(WhatsAppApiRequestOperation::SendTemplate, $log->operation);
            $this->assertSame(WhatsAppApiRequestOutcome::Success, $log->outcome);
            $this->assertSame('201111111111', $log->recipient_phone);
            $this->assertNotNull($log->whatsapp_message_id);
            $this->assertNotSame('', $log->summary);
            $this->assertNotSame('', $log->status_label);
        });
    }
}
