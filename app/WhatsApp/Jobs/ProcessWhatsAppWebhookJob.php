<?php

namespace App\WhatsApp\Jobs;

use App\Models\Tenant\WhatsAppNumber;
use App\Models\WhatsAppWebhookEvent;
use App\WhatsApp\Actions\ProcessInboundMessageAction;
use App\WhatsApp\Actions\ProcessMessageStatusAction;
use App\WhatsApp\Enums\WhatsAppWebhookProcessingStatus;
use App\WhatsApp\Services\WhatsAppWebhookPayloadRedactor;
use App\WhatsApp\Services\WhatsAppWebhookResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $webhookEventId,
    ) {}

    public function handle(
        WhatsAppWebhookResolver $resolver,
        ProcessInboundMessageAction $inboundAction,
        ProcessMessageStatusAction $statusAction,
        WhatsAppWebhookPayloadRedactor $redactor,
    ): void {
        $event = WhatsAppWebhookEvent::query()->find($this->webhookEventId);

        if ($event === null) {
            return;
        }

        $payload = $event->reprocessablePayload() ?? [];

        try {
            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];
                    $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
                    $registry = $resolver->resolveByPhoneNumberId($phoneNumberId);

                    if ($registry === null) {
                        $event->update([
                            'processing_status' => WhatsAppWebhookProcessingStatus::Unresolved,
                            'phone_number_id' => $phoneNumberId,
                            'error_message' => 'No registry entry for phone_number_id.',
                            'processed_at' => now(),
                        ]);

                        continue;
                    }

                    $tenant = $registry->tenant;
                    if ($tenant === null) {
                        continue;
                    }

                    $tenant->run(function () use ($registry, $value, $inboundAction, $statusAction) {
                        $number = WhatsAppNumber::query()->find($registry->tenant_whatsapp_number_id);

                        if ($number === null) {
                            return;
                        }

                        $contactName = $value['contacts'][0]['profile']['name'] ?? null;

                        foreach ($value['messages'] ?? [] as $message) {
                            $inboundAction->execute($number, $message, $contactName);
                        }

                        foreach ($value['statuses'] ?? [] as $status) {
                            $statusAction->execute($status);
                        }
                    });

                    $event->update([
                        'tenant_id' => $registry->tenant_id,
                        'phone_number_id' => $phoneNumberId,
                        'processing_status' => WhatsAppWebhookProcessingStatus::Processed,
                        'payload' => $redactor->redact($payload),
                        'payload_redacted' => config('whatsapp.webhook_payload_retention', 'minimized') !== 'full',
                        'processed_at' => now(),
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::channel(config('whatsapp.log_channel'))->error('WhatsApp webhook processing failed', [
                'event_id' => $event->id,
                'message' => $exception->getMessage(),
            ]);

            $event->update([
                'processing_status' => WhatsAppWebhookProcessingStatus::Failed,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        }
    }
}
