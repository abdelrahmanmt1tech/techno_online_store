<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\Tenant\WhatsAppTemplate;
use App\WhatsApp\Actions\SyncWhatsAppTemplatesFromMetaAction;
use App\WhatsApp\Enums\WhatsAppTemplateCategory;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;
use Illuminate\Support\Facades\Http;

class SyncWhatsAppTemplatesTest extends WhatsAppTestCase
{
    public function test_sync_creates_templates_from_meta_api(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => 'phone-123',
                'whatsapp_business_account_id' => 'waba-123',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_default' => true,
                'is_active' => true,
            ]);

            Http::fake([
                'graph.facebook.com/*' => Http::response([
                    'data' => [
                        [
                            'id' => 'tpl-1',
                            'name' => 'order_update',
                            'language' => 'ar',
                            'status' => 'APPROVED',
                            'category' => 'UTILITY',
                            'components' => [
                                [
                                    'type' => 'BODY',
                                    'text' => 'مرحباً {{1}}، طلبك {{2}} جاهز',
                                ],
                            ],
                        ],
                    ],
                    'paging' => [
                        'cursors' => [],
                    ],
                ]),
            ]);

            $result = app(SyncWhatsAppTemplatesFromMetaAction::class)->execute($number);

            $this->assertSame(1, $result->created);
            $this->assertSame(0, $result->updated);

            $template = WhatsAppTemplate::query()->first();

            $this->assertNotNull($template);
            $this->assertSame('order_update', $template->name);
            $this->assertSame('ar', $template->language);
            $this->assertSame(WhatsAppTemplateStatus::Approved, $template->status);
            $this->assertSame(WhatsAppTemplateCategory::Utility, $template->category);
            $this->assertSame('tpl-1', $template->provider_template_id);
            $this->assertSame(['{{1}}', '{{2}}'], $template->variables_schema);
            $this->assertNotNull($template->last_synced_at);
        });
    }

    public function test_sync_updates_existing_template_by_unique_key(): void
    {
        $tenant = $this->createTenantWithDatabase();

        $tenant->run(function () {
            $number = WhatsAppNumber::query()->create([
                'display_phone_number' => '+201234567890',
                'phone_number_id' => 'phone-123',
                'whatsapp_business_account_id' => 'waba-123',
                'access_token' => 'test-token',
                'status' => 'active',
                'is_default' => true,
                'is_active' => true,
            ]);

            WhatsAppTemplate::query()->create([
                'whatsapp_number_id' => $number->id,
                'whatsapp_business_account_id' => 'waba-123',
                'name' => 'order_update',
                'language' => 'ar',
                'category' => WhatsAppTemplateCategory::Utility,
                'status' => WhatsAppTemplateStatus::Pending,
                'components' => [],
            ]);

            Http::fake([
                'graph.facebook.com/*' => Http::response([
                    'data' => [
                        [
                            'id' => 'tpl-1',
                            'name' => 'order_update',
                            'language' => 'ar',
                            'status' => 'APPROVED',
                            'category' => 'UTILITY',
                            'components' => [
                                ['type' => 'BODY', 'text' => 'Updated {{1}}'],
                            ],
                        ],
                    ],
                ]),
            ]);

            $result = app(SyncWhatsAppTemplatesFromMetaAction::class)->execute($number);

            $this->assertSame(0, $result->created);
            $this->assertSame(1, $result->updated);
            $this->assertSame(WhatsAppTemplateStatus::Approved, WhatsAppTemplate::query()->first()->status);
        });
    }
}
