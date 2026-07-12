<?php

namespace App\Messenger\Actions;

use App\Messenger\Enums\MessengerMessageDirection;
use App\Messenger\Enums\MessengerMessageSenderType;
use App\Messenger\Enums\MessengerMessageStatus;
use App\Messenger\Enums\MessengerMessageType;
use App\Messenger\Enums\MessengerPageStatus;
use App\Messenger\Services\MessengerGraphApiService;
use App\Messenger\Services\MessengerSendingPolicyService;
use App\Models\Tenant\MessengerConversation;
use App\Models\Tenant\MessengerMessage;
use App\Models\Tenant\MessengerPage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\Response;
use RuntimeException;

class SendMessengerTextMessageAction
{
    public function __construct(
        protected MessengerSendingPolicyService $policy,
        protected MessengerGraphApiService $graphApi,
        protected SyncMessengerPageRegistryAction $syncRegistry,
    ) {}

    public function execute(
        MessengerConversation $conversation,
        string $body,
        ?Authenticatable $user = null,
    ): MessengerMessage {
        $page = $conversation->messengerPage;

        if ($page === null) {
            throw new RuntimeException('Messenger page not found for conversation.');
        }

        $policy = $this->policy->canSendText($page, $conversation);

        if (! $policy->allowed) {
            throw new RuntimeException($policy->reason ?? 'Sending not allowed.');
        }

        $message = $conversation->messages()->create([
            'messenger_page_id' => $page->id,
            'direction' => MessengerMessageDirection::Outbound,
            'sender_type' => $user ? MessengerMessageSenderType::Agent : MessengerMessageSenderType::System,
            'type' => MessengerMessageType::Text,
            'body' => $body,
            'status' => MessengerMessageStatus::Pending,
        ]);

        $response = $this->graphApi->sendText($page, $conversation->sender_psid, $body);
        $this->graphApi->attachLastLoggedRequestToMessage($message->id);

        if ($response->failed()) {
            $this->handleApiFailure($page, $message, $response);

            throw new RuntimeException($this->graphApi->safeErrorMessage($response));
        }

        $providerMessageId = $response->json('message_id');

        $message->update([
            'provider_message_id' => is_string($providerMessageId) ? $providerMessageId : null,
            'status' => MessengerMessageStatus::Sent,
            'sent_at' => now(),
            'raw_payload' => [
                'request' => ['type' => 'text'],
                'response' => $response->json(),
            ],
        ]);

        $now = now();
        $conversation->update([
            'last_message_preview' => mb_substr($body, 0, 255),
            'last_message_at' => $now,
            'last_outbound_message_at' => $now,
        ]);

        $page->update(['last_outbound_at' => $now]);
        $this->syncRegistry->execute($page->fresh());

        return $message->fresh();
    }

    protected function handleApiFailure(
        MessengerPage $page,
        MessengerMessage $message,
        Response $response,
    ): void {
        $errorMessage = $this->graphApi->safeErrorMessage($response);

        $pageUpdates = ['last_error_message' => $errorMessage];

        if ($this->graphApi->isAuthenticationError($response)) {
            $pageUpdates['status'] = MessengerPageStatus::ReconnectRequired;
            $pageUpdates['reconnect_required_at'] = now();
        }

        $page->update($pageUpdates);
        $this->syncRegistry->execute($page->fresh());

        $message->update([
            'status' => MessengerMessageStatus::Failed,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'error_code' => $this->stringifyErrorCode($response->json('error.code')),
        ]);
    }

    protected function stringifyErrorCode(mixed $code): ?string
    {
        if ($code === null) {
            return null;
        }

        return is_scalar($code) ? (string) $code : null;
    }
}
