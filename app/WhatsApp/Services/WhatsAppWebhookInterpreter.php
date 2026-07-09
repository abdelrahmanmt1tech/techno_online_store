<?php

namespace App\WhatsApp\Services;

class WhatsAppWebhookInterpreter
{
    /**
     * @param  array<string, mixed>|null  $payload
     * @return array{summary: string, kind: string, details: array<int, array<string, mixed>>}
     */
    public function interpret(?array $payload, ?string $eventType = null, ?bool $signatureValid = null): array
    {
        if ($eventType === 'invalid_signature') {
            return [
                'summary' => __('dashboard.whatsapp_webhook_summary_invalid_signature'),
                'kind' => 'security',
                'details' => [
                    $this->detailBlock(
                        __('dashboard.whatsapp_webhook_security_rejection'),
                        [
                            __('dashboard.whatsapp_signature_valid') => $signatureValid === false
                                ? __('dashboard.no')
                                : __('dashboard.whatsapp_webhook_signature_unknown'),
                            __('dashboard.description') => __('dashboard.whatsapp_webhook_invalid_signature_help'),
                        ],
                    ),
                ],
            ];
        }

        if ($payload === null || $payload === []) {
            return [
                'summary' => __('dashboard.whatsapp_webhook_summary_empty'),
                'kind' => 'unknown',
                'details' => [],
            ];
        }

        $details = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $field = (string) ($change['field'] ?? 'unknown');
                $value = is_array($change['value'] ?? null) ? $change['value'] : [];

                foreach ($value['messages'] ?? [] as $message) {
                    if (! is_array($message)) {
                        continue;
                    }

                    $details[] = $this->detailBlock(
                        __('dashboard.whatsapp_webhook_inbound_message'),
                        [
                            __('dashboard.whatsapp_event_type') => $field,
                            __('dashboard.whatsapp_message_type') => (string) ($message['type'] ?? '—'),
                            __('dashboard.whatsapp_customer_phone') => (string) ($message['from'] ?? '—'),
                            __('dashboard.whatsapp_customer_name') => $this->contactName($value),
                            __('dashboard.whatsapp_message_id') => (string) ($message['id'] ?? '—'),
                            __('dashboard.whatsapp_message_preview') => $this->messagePreview($message),
                            __('dashboard.created_at') => $this->formatTimestamp($message['timestamp'] ?? null),
                        ],
                        'inbound_message',
                    );
                }

                foreach ($value['statuses'] ?? [] as $status) {
                    if (! is_array($status)) {
                        continue;
                    }

                    $details[] = $this->detailBlock(
                        __('dashboard.whatsapp_webhook_status_update'),
                        [
                            __('dashboard.whatsapp_event_type') => $field,
                            __('dashboard.whatsapp_message_id') => (string) ($status['id'] ?? '—'),
                            __('dashboard.whatsapp_delivery_status') => $this->deliveryStatusLabel((string) ($status['status'] ?? '')),
                            __('dashboard.whatsapp_customer_phone') => (string) ($status['recipient_id'] ?? '—'),
                            __('dashboard.created_at') => $this->formatTimestamp($status['timestamp'] ?? null),
                        ],
                        'status_update',
                    );
                }

                if (($value['messages'] ?? []) === [] && ($value['statuses'] ?? []) === []) {
                    $details[] = $this->detailBlock(
                        __('dashboard.whatsapp_webhook_other_event'),
                        [
                            __('dashboard.whatsapp_event_type') => $field,
                            __('dashboard.whatsapp_phone_number_id') => (string) ($value['metadata']['phone_number_id'] ?? '—'),
                            __('dashboard.whatsapp_business_name') => (string) ($value['metadata']['display_phone_number'] ?? '—'),
                        ],
                        'other',
                    );
                }
            }
        }

        return [
            'summary' => $this->buildSummary($details, $eventType),
            'kind' => $details[0]['type'] ?? 'unknown',
            'details' => $details,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $details
     */
    protected function buildSummary(array $details, ?string $eventType): string
    {
        if ($details === []) {
            return $eventType
                ? __('dashboard.whatsapp_webhook_summary_event', ['type' => $eventType])
                : __('dashboard.whatsapp_webhook_summary_empty');
        }

        $first = $details[0];

        return match ($first['type'] ?? 'unknown') {
            'inbound_message' => __('dashboard.whatsapp_webhook_summary_inbound', [
                'type' => $first['items'][__('dashboard.whatsapp_message_type')] ?? '—',
                'phone' => $first['items'][__('dashboard.whatsapp_customer_phone')] ?? '—',
            ]),
            'status_update' => __('dashboard.whatsapp_webhook_summary_status', [
                'status' => $first['items'][__('dashboard.whatsapp_delivery_status')] ?? '—',
            ]),
            default => __('dashboard.whatsapp_webhook_summary_event', ['type' => $eventType ?? '—']),
        };
    }

    /**
     * @param  array<string, string>  $items
     * @return array<string, mixed>
     */
    protected function detailBlock(string $title, array $items, string $type = 'other'): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'items' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    protected function contactName(array $value): string
    {
        $contact = $value['contacts'][0] ?? null;

        if (! is_array($contact)) {
            return '—';
        }

        return (string) ($contact['profile']['name'] ?? '—');
    }

    /**
     * @param  array<string, mixed>  $message
     */
    protected function messagePreview(array $message): string
    {
        return match ($message['type'] ?? null) {
            'text' => (string) ($message['text']['body'] ?? '—'),
            'image' => __('dashboard.whatsapp_message_type_image'),
            'audio' => __('dashboard.whatsapp_message_type_audio'),
            'video' => __('dashboard.whatsapp_message_type_video'),
            'document' => __('dashboard.whatsapp_message_type_document'),
            'location' => __('dashboard.whatsapp_message_type_location'),
            'contacts' => __('dashboard.whatsapp_message_type_contacts'),
            'interactive' => __('dashboard.whatsapp_message_type_interactive'),
            'button' => __('dashboard.whatsapp_message_type_button'),
            'template' => __('dashboard.whatsapp_message_type_template'),
            default => (string) ($message['type'] ?? '—'),
        };
    }

    protected function deliveryStatusLabel(string $status): string
    {
        return match ($status) {
            'sent' => __('dashboard.whatsapp_delivery_sent'),
            'delivered' => __('dashboard.whatsapp_delivery_delivered'),
            'read' => __('dashboard.whatsapp_delivery_read'),
            'failed' => __('dashboard.whatsapp_delivery_failed'),
            default => $status !== '' ? $status : '—',
        };
    }

    protected function formatTimestamp(mixed $timestamp): string
    {
        if (! is_numeric($timestamp)) {
            return '—';
        }

        return now()->setTimestamp((int) $timestamp)->toDateTimeString();
    }
}
