<?php

namespace App\Messenger\Actions;

use App\Messenger\Enums\MessengerConversationStatus;
use App\Messenger\Enums\MessengerMessageDirection;
use App\Messenger\Enums\MessengerMessageSenderType;
use App\Messenger\Enums\MessengerMessageStatus;
use App\Messenger\Enums\MessengerMessageType;
use App\Messenger\Services\MessengerUserProfileService;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Throwable;

class ProcessInboundMessengerMessageAction
{
    public function __construct(
        protected UpsertMessengerContactAction $upsertContact,
        protected FindOrCreateMessengerConversationAction $findOrCreateConversation,
        protected OpenMessengerServiceWindowAction $openWindow,
        protected SyncMessengerPageRegistryAction $syncRegistry,
        protected MessengerUserProfileService $userProfile,
    ) {}

    /**
     * @param  array<string, mixed>  $messaging
     */
    public function execute(MessengerPage $page, array $messaging): void
    {
        $message = $messaging['message'] ?? null;

        if (! is_array($message)) {
            return;
        }

        if (($message['is_echo'] ?? false) === true) {
            return;
        }

        $senderPsid = (string) ($messaging['sender']['id'] ?? '');

        if ($senderPsid === '' || $senderPsid === (string) $page->page_id) {
            return;
        }

        $providerMessageId = $message['mid'] ?? null;

        if (blank($providerMessageId)) {
            return;
        }

        if (MessengerMessage::query()->where('provider_message_id', $providerMessageId)->exists()) {
            return;
        }

        $receivedAt = $this->parseTimestamp($messaging['timestamp'] ?? null);
        $webhookName = Arr::get($messaging, 'sender.name');
        $webhookName = is_string($webhookName) && trim($webhookName) !== '' ? trim($webhookName) : null;

        $profileName = $webhookName;
        $profilePictureUrl = null;

        try {
            $profile = $this->userProfile->fetch($page, $senderPsid);

            if ($profile !== null) {
                if ($profile->hasDisplayName()) {
                    $profileName = $profile->profileName;
                }

                if (filled($profile->profilePictureUrl)) {
                    $profilePictureUrl = $profile->profilePictureUrl;
                }
            }
        } catch (Throwable) {
            // Profile lookup must never fail inbound processing.
        }

        $contact = $this->upsertContact->execute(
            $senderPsid,
            $profileName,
            $receivedAt,
            $profilePictureUrl,
        );

        $conversation = $this->findOrCreateConversation->execute(
            $page,
            $senderPsid,
            $contact->id,
            $contact->profile_name,
        );
        $this->openWindow->execute($conversation, $receivedAt);

        [$type, $body, $mediaMetadata] = $this->parseMessageContent($message);

        $conversation->messages()->create([
            'messenger_page_id' => $page->id,
            'provider_message_id' => $providerMessageId,
            'direction' => MessengerMessageDirection::Inbound,
            'sender_type' => MessengerMessageSenderType::Customer,
            'type' => $type,
            'body' => $body,
            'media_metadata' => $mediaMetadata,
            'raw_payload' => $messaging,
            'status' => MessengerMessageStatus::Received,
            'received_at' => $receivedAt,
        ]);

        $preview = $body ?: '['.$type.']';
        $conversation->update([
            'last_message_preview' => mb_substr($preview, 0, 255),
            'last_message_at' => $receivedAt,
            'status' => MessengerConversationStatus::Open,
            'contact_id' => $contact->id,
            'customer_name' => $contact->profile_name ?: $conversation->customer_name,
        ]);

        $page->update(['last_inbound_at' => $receivedAt]);
        $this->syncRegistry->execute($page->fresh());
    }

    protected function parseTimestamp(mixed $timestamp): Carbon
    {
        if ($timestamp === null || $timestamp === '') {
            return now();
        }

        $value = (int) $timestamp;

        if ($value > 1_000_000_000_000) {
            return Carbon::createFromTimestampMs($value);
        }

        return Carbon::createFromTimestamp($value);
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{0: string, 1: ?string, 2: ?array<string, mixed>}
     */
    protected function parseMessageContent(array $message): array
    {
        if (isset($message['text']) && is_string($message['text'])) {
            return [MessengerMessageType::Text->value, $message['text'], null];
        }

        $attachments = $message['attachments'] ?? null;

        if (is_array($attachments) && $attachments !== []) {
            $first = $attachments[0];
            $attachmentType = is_array($first) ? (string) ($first['type'] ?? 'other') : 'other';

            $type = match ($attachmentType) {
                'image' => MessengerMessageType::Image->value,
                'audio' => MessengerMessageType::Audio->value,
                'video' => MessengerMessageType::Video->value,
                'file' => MessengerMessageType::File->value,
                default => MessengerMessageType::Other->value,
            };

            return [$type, null, ['attachments' => $attachments]];
        }

        if (isset($message['quick_reply']) || isset($message['postback'])) {
            return [
                MessengerMessageType::Postback->value,
                Arr::get($message, 'quick_reply.payload') ?? Arr::get($message, 'postback.payload'),
                Arr::only($message, ['quick_reply', 'postback']),
            ];
        }

        return [MessengerMessageType::Other->value, null, ['raw' => Arr::except($message, ['mid', 'is_echo'])]];
    }
}
