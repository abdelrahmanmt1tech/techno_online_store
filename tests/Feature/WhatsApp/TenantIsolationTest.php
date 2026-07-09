<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppConversation;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\Models\TenantUser;
use App\WhatsApp\Actions\SendWhatsAppTemplateMessageAction;
use App\WhatsApp\DTOs\SendTemplateMessageData;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use Spatie\Permission\Models\Permission;

class TenantIsolationTest extends WhatsAppTestCase
{
    public function test_tenant_cannot_use_another_tenants_template(): void
    {
        $tenantA = $this->createTenantWithDatabase();
        $tenantB = $this->createTenantWithDatabase();

        $templateOnB = null;

        $tenantB->run(function () use (&$templateOnB) {
            $templateOnB = WhatsAppTemplate::query()->create([
                'whatsapp_business_account_id' => 'waba-b',
                'name' => 'hello',
                'language' => 'en',
                'category' => 'utility',
                'status' => WhatsAppTemplateStatus::Approved,
            ]);
        });

        $tenantA->run(function () use ($templateOnB) {
            $this->assertNull(WhatsAppTemplate::query()->find($templateOnB->id));
        });
    }

    public function test_unapproved_template_cannot_be_sent(): void
    {
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
            ]);

            $conversation = WhatsAppConversation::query()->create([
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201111111111',
                'status' => 'open',
                'customer_service_window_expires_at' => now()->addHour(),
            ]);

            $template = WhatsAppTemplate::query()->create([
                'whatsapp_business_account_id' => 'waba-1',
                'name' => 'pending_template',
                'language' => 'en',
                'category' => 'utility',
                'status' => WhatsAppTemplateStatus::Pending,
            ]);

            $this->expectException(\RuntimeException::class);

            app(SendWhatsAppTemplateMessageAction::class)->execute(
                new SendTemplateMessageData($number, $conversation, $template),
                $user,
            );
        });
    }
}
