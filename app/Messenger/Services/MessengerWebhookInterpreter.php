<?php

namespace App\Messenger\Services;

class MessengerWebhookInterpreter
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{summary: string, kind: string, details: array<int, array<string, mixed>>}
     */
    public function interpret(?array $payload, ?string $eventType = null, ?bool $signatureValid = null): array
    {
        if ($eventType === 'invalid_signature') {
            return [
                'summary' => 'Messenger webhook rejected: invalid signature',
                'kind' => 'security',
                'details' => [],
            ];
        }

        if ($payload === null || $payload === []) {
            return [
                'summary' => 'Empty Messenger webhook',
                'kind' => 'unknown',
                'details' => [],
            ];
        }

        $details = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            $pageId = (string) ($entry['id'] ?? '—');

            foreach ($entry['messaging'] ?? [] as $messaging) {
                if (! is_array($messaging)) {
                    continue;
                }

                $text = $messaging['message']['text'] ?? null;
                $details[] = [
                    'type' => 'inbound_message',
                    'title' => 'Incoming Messenger message',
                    'items' => [
                        'page_id' => $pageId,
                        'psid' => (string) ($messaging['sender']['id'] ?? '—'),
                        'mid' => (string) ($messaging['message']['mid'] ?? '—'),
                        'preview' => is_string($text) ? $text : '[non-text]',
                    ],
                ];
            }
        }

        $first = $details[0]['items'] ?? null;

        return [
            'summary' => $first
                ? 'Messenger message from '.($first['psid'] ?? '—').' on page '.($first['page_id'] ?? '—')
                : 'Messenger webhook event: '.($eventType ?? 'page'),
            'kind' => $details[0]['type'] ?? 'other',
            'details' => $details,
        ];
    }
}
