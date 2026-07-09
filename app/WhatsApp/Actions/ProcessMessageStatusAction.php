<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppMessage;
use App\WhatsApp\Enums\WhatsAppMessageStatus;
use Carbon\Carbon;

class ProcessMessageStatusAction
{
    /**
     * @param  array<string, mixed>  $statusPayload
     */
    public function execute(array $statusPayload): void
    {
        $providerMessageId = $statusPayload['id'] ?? null;
        $statusValue = $statusPayload['status'] ?? null;

        if (blank($providerMessageId) || blank($statusValue)) {
            return;
        }

        $message = WhatsAppMessage::query()
            ->where('provider_message_id', $providerMessageId)
            ->first();

        if ($message === null) {
            return;
        }

        $newStatus = $this->mapStatus((string) $statusValue);
        $currentStatus = $message->status instanceof WhatsAppMessageStatus
            ? $message->status
            : WhatsAppMessageStatus::from((string) $message->status);

        if (! $currentStatus->canTransitionTo($newStatus)) {
            return;
        }

        $timestamp = isset($statusPayload['timestamp'])
            ? Carbon::createFromTimestamp((int) $statusPayload['timestamp'])
            : now();

        $updates = ['status' => $newStatus->value];

        match ($newStatus) {
            WhatsAppMessageStatus::Sent => $updates['sent_at'] = $timestamp,
            WhatsAppMessageStatus::Delivered => $updates['delivered_at'] = $timestamp,
            WhatsAppMessageStatus::Read => $updates['read_at'] = $timestamp,
            WhatsAppMessageStatus::Failed => $updates = array_merge($updates, [
                'failed_at' => $timestamp,
                'error_code' => data_get($statusPayload, 'errors.0.code'),
                'error_message' => data_get($statusPayload, 'errors.0.title'),
            ]),
            default => null,
        };

        $message->update($updates);
    }

    protected function mapStatus(string $status): WhatsAppMessageStatus
    {
        return match ($status) {
            'sent' => WhatsAppMessageStatus::Sent,
            'delivered' => WhatsAppMessageStatus::Delivered,
            'read' => WhatsAppMessageStatus::Read,
            'failed' => WhatsAppMessageStatus::Failed,
            default => WhatsAppMessageStatus::Pending,
        };
    }
}
