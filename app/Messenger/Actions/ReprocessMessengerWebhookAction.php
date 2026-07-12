<?php

namespace App\Messenger\Actions;

use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Messenger\Jobs\ProcessMessengerWebhookJob;
use App\Models\MessengerWebhookEvent;
use RuntimeException;

class ReprocessMessengerWebhookAction
{
    public function execute(MessengerWebhookEvent $event): void
    {
        if (! $event->canReprocess()) {
            throw new RuntimeException(__('dashboard.messenger_reprocess_webhook_unavailable'));
        }

        $event->update([
            'processing_status' => MessengerWebhookProcessingStatus::Pending,
            'error_message' => null,
            'processed_at' => null,
        ]);

        ProcessMessengerWebhookJob::dispatch($event->id);
    }
}
