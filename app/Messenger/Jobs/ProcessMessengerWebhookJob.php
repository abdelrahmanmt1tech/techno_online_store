<?php

namespace App\Messenger\Jobs;

use App\Messenger\Actions\ProcessInboundMessengerMessageAction;
use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Messenger\Services\MessengerWebhookPayloadRedactor;
use App\Messenger\Services\MessengerWebhookResolver;
use App\Models\MessengerWebhookEvent;
use App\Models\Tenant\MessengerPage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMessengerWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $webhookEventId,
    ) {}

    public function handle(
        MessengerWebhookResolver $resolver,
        ProcessInboundMessengerMessageAction $inboundAction,
        MessengerWebhookPayloadRedactor $redactor,
    ): void {
        $event = MessengerWebhookEvent::query()->find($this->webhookEventId);

        if ($event === null) {
            return;
        }

        $payload = $event->reprocessablePayload() ?? [];

        try {
            $resolvedAny = false;
            $lastTenantId = null;
            $lastPageId = null;

            foreach ($payload['entry'] ?? [] as $entry) {
                $pageId = isset($entry['id']) ? (string) $entry['id'] : null;
                $lastPageId = $pageId;
                $registry = $resolver->resolveByPageId($pageId);

                if ($registry === null) {
                    $event->update([
                        'processing_status' => MessengerWebhookProcessingStatus::Unresolved,
                        'page_id' => $pageId,
                        'error_message' => 'No registry entry for page_id.',
                        'processed_at' => now(),
                    ]);

                    continue;
                }

                $tenant = $registry->tenant;
                if ($tenant === null) {
                    $event->update([
                        'processing_status' => MessengerWebhookProcessingStatus::Unresolved,
                        'page_id' => $pageId,
                        'tenant_id' => $registry->tenant_id,
                        'error_message' => 'Registry tenant not found.',
                        'processed_at' => now(),
                    ]);

                    continue;
                }

                $resolvedAny = true;
                $lastTenantId = $registry->tenant_id;

                $tenant->run(function () use ($registry, $entry, $inboundAction) {
                    $page = MessengerPage::query()->find($registry->tenant_messenger_page_id);

                    if ($page === null) {
                        return;
                    }

                    foreach ($entry['messaging'] ?? [] as $messaging) {
                        if (! is_array($messaging)) {
                            continue;
                        }

                        $inboundAction->execute($page, $messaging);
                    }
                });

                $event->update([
                    'tenant_id' => $registry->tenant_id,
                    'page_id' => $pageId,
                    'processing_status' => MessengerWebhookProcessingStatus::Processed,
                    'error_message' => null,
                    'payload' => $redactor->redact($payload),
                    'payload_redacted' => config('messenger.webhook_payload_retention', 'minimized') !== 'full',
                    'processed_at' => now(),
                ]);
            }

            if (! $resolvedAny && $event->processing_status !== MessengerWebhookProcessingStatus::Unresolved) {
                $event->update([
                    'processing_status' => MessengerWebhookProcessingStatus::Unresolved,
                    'page_id' => $lastPageId,
                    'tenant_id' => $lastTenantId,
                    'error_message' => $event->error_message ?? 'No processable Messenger entries.',
                    'processed_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::channel(config('messenger.log_channel'))->error('Messenger webhook processing failed', [
                'event_id' => $event->id,
                'message' => $exception->getMessage(),
            ]);

            $event->update([
                'processing_status' => MessengerWebhookProcessingStatus::Failed,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        }
    }
}
