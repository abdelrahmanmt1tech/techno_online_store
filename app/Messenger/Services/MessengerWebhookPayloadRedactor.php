<?php

namespace App\Messenger\Services;

class MessengerWebhookPayloadRedactor
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function redact(array $payload): array
    {
        $retention = config('messenger.webhook_payload_retention', 'minimized');

        if ($retention === 'full') {
            return $payload;
        }

        if ($retention === 'metadata') {
            return [
                'object' => $payload['object'] ?? null,
                'page_ids' => collect($payload['entry'] ?? [])
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->all(),
            ];
        }

        $redacted = $payload;

        foreach ($redacted['entry'] ?? [] as $entryIndex => $entry) {
            foreach ($entry['messaging'] ?? [] as $messagingIndex => $messaging) {
                if (! is_array($messaging)) {
                    continue;
                }

                $message = $messaging['message'] ?? null;

                $redacted['entry'][$entryIndex]['messaging'][$messagingIndex] = [
                    'sender' => ['id' => '[redacted]'],
                    'recipient' => ['id' => $messaging['recipient']['id'] ?? null],
                    'timestamp' => $messaging['timestamp'] ?? null,
                    'message' => is_array($message) ? [
                        'mid' => $message['mid'] ?? null,
                        'text' => isset($message['text']) ? '[redacted]' : null,
                    ] : null,
                ];
            }
        }

        return $redacted;
    }
}
