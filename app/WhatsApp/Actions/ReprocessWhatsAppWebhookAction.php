<?php

namespace App\WhatsApp\Actions;

use App\Models\WhatsAppWebhookEvent;
use App\WhatsApp\Enums\WhatsAppWebhookProcessingStatus;
use App\WhatsApp\Jobs\ProcessWhatsAppWebhookJob;
use RuntimeException;

class ReprocessWhatsAppWebhookAction
{
    public function execute(WhatsAppWebhookEvent $event): void
    {
        if (! $event->canReprocess()) {
            throw new RuntimeException(__('dashboard.whatsapp_webhook_reprocess_unavailable'));
        }

        $event->update([
            'processing_status' => WhatsAppWebhookProcessingStatus::Pending,
            'error_message' => null,
            'processed_at' => null,
        ]);

        ProcessWhatsAppWebhookJob::dispatch($event->id);
    }
}
