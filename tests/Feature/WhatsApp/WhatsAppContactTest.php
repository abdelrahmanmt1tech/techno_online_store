<?php

namespace Tests\Feature\WhatsApp;

use App\Filament\Shared\WhatsApp\Actions\SendWhatsAppMessageFilamentAction;
use App\Models\Tenant\WhatsAppContact;
use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\Models\TenantUser;
use App\WhatsApp\Enums\WhatsAppTemplateCategory;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use Illuminate\Support\Facades\Http;

class WhatsAppContactTest extends WhatsAppTestCase
{
    public function test_contact_phone_is_normalized_on_save(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $contact = WhatsAppContact::query()->create([
                'phone' => '+20 100 696 0579',
                'profile_name' => 'Ahmed',
            ]);

            $this->assertSame('201006960579', $contact->phone);
        });
    }

    public function test_send_message_action_creates_contact_conversation_and_sends_template(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $user = TenantUser::query()->create([
                'name' => 'Store Admin',
                'email' => 'admin@store.test',
                'password' => 'password',
            ]);

            $this->actingAs($user, 'tenant');

            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => 'phone-123',
                'whatsapp_business_account_id' => 'waba-123',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_default' => true,
                'is_active' => true,
            ]);

            $template = WhatsAppTemplate::query()->create([
                'whatsapp_number_id' => $number->id,
                'whatsapp_business_account_id' => 'waba-123',
                'name' => 'hello_world',
                'language' => 'en_US',
                'category' => WhatsAppTemplateCategory::Utility,
                'status' => WhatsAppTemplateStatus::Approved,
                'components' => [],
            ]);

            Http::fake([
                'graph.facebook.com/*' => Http::response([
                    'messages' => [['id' => 'wamid.OUT123']],
                ]),
            ]);

            SendWhatsAppMessageFilamentAction::dispatch([
                'whatsapp_number_id' => $number->id,
                'message_type' => 'template',
                'template_id' => $template->id,
                'template_variables' => [],
            ], '201006960579', 'Ahmed');

            $this->assertDatabaseHas('whatsapp_contacts', [
                'phone' => '201006960579',
                'profile_name' => 'Ahmed',
            ]);

            $this->assertDatabaseHas('whatsapp_conversations', [
                'whatsapp_number_id' => $number->id,
                'customer_phone' => '201006960579',
                'customer_name' => 'Ahmed',
            ]);

            $this->assertDatabaseHas('whatsapp_messages', [
                'whatsapp_number_id' => $number->id,
                'template_id' => $template->id,
                'status' => 'sent',
            ]);
        });
    }
}
