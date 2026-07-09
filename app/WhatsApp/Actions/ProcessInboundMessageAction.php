<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppMessage;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Enums\WhatsAppMessageDirection;
use App\WhatsApp\Enums\WhatsAppMessageSenderType;
use App\WhatsApp\Enums\WhatsAppMessageStatus;
use App\WhatsApp\Enums\WhatsAppMessageType;
use App\WhatsApp\Events\WhatsAppMessageReceived;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ProcessInboundMessageAction
{
    public function __construct(
        protected FindOrCreateConversationAction $findOrCreateConversation,
        protected UpsertWhatsAppContactAction $upsertContact,
        protected OpenCustomerServiceWindowAction $openWindow,
        protected SyncWhatsAppNumberRegistryAction $syncRegistry,
    ) {}

    /**
     * @param  array<string, mixed>  $message
     */
    public function execute(WhatsAppNumber $number, array $message, ?string $contactName = null): void
    {
        $providerMessageId = $message['id'] ?? null;

        if (blank($providerMessageId)) {
            return;
        }

        if (WhatsAppMessage::query()->where('provider_message_id', $providerMessageId)->exists()) {
            return;
        }

        $customerPhone = (string) ($message['from'] ?? '');
        $receivedAt = isset($message['timestamp'])
            ? Carbon::createFromTimestamp((int) $message['timestamp'])
            : now();

        $this->upsertContact->execute($customerPhone, $contactName, $receivedAt);

        $conversation = $this->findOrCreateConversation->execute($number, $customerPhone, $contactName);
        $this->openWindow->execute($conversation, $receivedAt);

        [$type, $body, $mediaMetadata] = $this->parseMessageContent($message);

        $whatsappMessage = $conversation->messages()->create([
            'whatsapp_number_id' => $number->id,
            'provider_message_id' => $providerMessageId,
            'direction' => WhatsAppMessageDirection::Inbound,
            'sender_type' => WhatsAppMessageSenderType::Customer,
            'type' => $type,
            'body' => $body,
            'media_metadata' => $mediaMetadata,
            'raw_payload' => $message,
            'status' => WhatsAppMessageStatus::Received,
            'received_at' => $receivedAt,
        ]);

        $preview = $body ?: '['.$type.']';
        $conversation->update([
            'last_message_preview' => mb_substr($preview, 0, 255),
            'last_message_at' => $receivedAt,
            'status' => 'open',
        ]);

        $number->update(['last_inbound_at' => $receivedAt]);
        $this->syncRegistry->execute($number->fresh());

        event(new WhatsAppMessageReceived($whatsappMessage));
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{0: string, 1: ?string, 2: ?array<string, mixed>}
     */
    protected function parseMessageContent(array $message): array
    {
        $type = (string) ($message['type'] ?? 'unsupported');

        return match ($type) {
            'text' => [
                WhatsAppMessageType::Text->value,
                Arr::get($message, 'text.body'),
                null,
            ],
            'image', 'video', 'audio', 'document' => [
                $type,
                null,
                Arr::get($message, $type),
            ],
            'location' => [
                WhatsAppMessageType::Location->value,
                null,
                Arr::get($message, 'location'),
            ],
            'interactive' => [
                WhatsAppMessageType::Interactive->value,
                Arr::get($message, 'interactive.body.text'),
                Arr::get($message, 'interactive'),
            ],
            default => [
                WhatsAppMessageType::Unsupported->value,
                null,
                ['raw_type' => $type],
            ],
        };
    }
}
