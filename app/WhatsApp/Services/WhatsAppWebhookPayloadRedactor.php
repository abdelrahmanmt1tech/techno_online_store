<?php

namespace App\WhatsApp\Services;

class WhatsAppWebhookPayloadRedactor
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function redact(array $payload): array
    {
        $retention = config('whatsapp.webhook_payload_retention', 'minimized');

        if ($retention === 'full') {
            return $payload;
        }

        if ($retention === 'metadata') {
            return $this->extractMetadata($payload);
        }

        return $this->minimize($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function minimize(array $payload): array
    {
        $redacted = $payload;

        foreach ($redacted['entry'] ?? [] as $entryIndex => $entry) {
            foreach ($entry['changes'] ?? [] as $changeIndex => $change) {
                $value = $change['value'] ?? [];

                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $messageIndex => $message) {
                        $redacted['entry'][$entryIndex]['changes'][$changeIndex]['value']['messages'][$messageIndex] = [
                            'id' => $message['id'] ?? null,
                            'type' => $message['type'] ?? null,
                            'from' => '[redacted]',
                            'timestamp' => $message['timestamp'] ?? null,
                        ];
                    }
                }

                if (isset($value['contacts'])) {
                    $redacted['entry'][$entryIndex]['changes'][$changeIndex]['value']['contacts'] = '[redacted]';
                }

                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $statusIndex => $status) {
                        $redacted['entry'][$entryIndex]['changes'][$changeIndex]['value']['statuses'][$statusIndex] = [
                            'id' => $status['id'] ?? null,
                            'status' => $status['status'] ?? null,
                            'timestamp' => $status['timestamp'] ?? null,
                            'recipient_id' => '[redacted]',
                        ];
                    }
                }
            }
        }

        return $redacted;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function extractMetadata(array $payload): array
    {
        $metadata = [
            'object' => $payload['object'] ?? null,
            'phone_number_ids' => [],
            'event_types' => [],
        ];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $metadata['event_types'][] = $change['field'] ?? null;
                $phoneNumberId = $change['value']['metadata']['phone_number_id'] ?? null;
                if ($phoneNumberId) {
                    $metadata['phone_number_ids'][] = $phoneNumberId;
                }
            }
        }

        $metadata['phone_number_ids'] = array_values(array_unique($metadata['phone_number_ids']));
        $metadata['event_types'] = array_values(array_unique(array_filter($metadata['event_types'])));

        return $metadata;
    }
}
