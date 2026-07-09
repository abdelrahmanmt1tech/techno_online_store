<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppMessage;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\DTOs\SendTextMessageData;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppMessageDirection;
use App\WhatsApp\Enums\WhatsAppMessageSenderType;
use App\WhatsApp\Enums\WhatsAppMessageStatus;
use App\WhatsApp\Enums\WhatsAppMessageType;
use App\WhatsApp\Events\WhatsAppMessageFailed;
use App\WhatsApp\Events\WhatsAppMessageSent;
use App\WhatsApp\Services\WhatsAppCloudApiService;
use App\WhatsApp\Services\WhatsAppSendingPolicyService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SendWhatsAppTextMessageAction
{
    public function __construct(
        protected WhatsAppSendingPolicyService $policy,
        protected WhatsAppCloudApiService $cloudApi,
        protected SyncWhatsAppNumberRegistryAction $syncRegistry,
    ) {}

    public function execute(SendTextMessageData $data, ?Authenticatable $user = null, string $guard = 'tenant'): WhatsAppMessage
    {
        $policy = $this->policy->canSendText($user, $data->whatsappNumber, $data->conversation, $guard);

        if (! $policy->allowed) {
            throw new RuntimeException($policy->reason ?? 'Sending not allowed.');
        }

        return DB::transaction(function () use ($data, $user) {
            $message = $data->conversation->messages()->create([
                'whatsapp_number_id' => $data->whatsappNumber->id,
                'direction' => WhatsAppMessageDirection::Outbound,
                'sender_type' => $user ? WhatsAppMessageSenderType::Agent : WhatsAppMessageSenderType::System,
                'type' => WhatsAppMessageType::Text,
                'body' => $data->body,
                'status' => WhatsAppMessageStatus::Pending,
            ]);

            $response = $this->cloudApi->sendText(
                $data->whatsappNumber,
                $data->conversation->customer_phone,
                $data->body,
            );

            if ($response->failed()) {
                $this->handleApiFailure($data->whatsappNumber, $message, $response);

                throw new RuntimeException($this->cloudApi->safeErrorMessage($response));
            }

            $providerMessageId = $response->json('messages.0.id');

            $message->update([
                'provider_message_id' => $providerMessageId,
                'status' => WhatsAppMessageStatus::Sent,
                'sent_at' => now(),
                'raw_payload' => ['request' => ['type' => 'text'], 'response' => $response->json()],
            ]);

            $now = now();
            $data->conversation->update([
                'last_message_preview' => mb_substr($data->body, 0, 255),
                'last_message_at' => $now,
                'last_outbound_message_at' => $now,
            ]);

            $data->whatsappNumber->update(['last_outbound_at' => $now]);
            $this->syncRegistry->execute($data->whatsappNumber->fresh());

            event(new WhatsAppMessageSent($message->fresh()));

            return $message->fresh();
        });
    }

    protected function handleApiFailure(
        WhatsAppNumber $number,
        WhatsAppMessage $message,
        Response $response,
    ): void {
        $errorMessage = $this->cloudApi->safeErrorMessage($response);

        $numberUpdates = ['last_error_message' => $errorMessage];
        if ($this->cloudApi->isAuthenticationError($response)) {
            $numberUpdates['status'] = WhatsAppConnectionStatus::ReconnectRequired;
        }

        $number->update($numberUpdates);
        $this->syncRegistry->execute($number->fresh());

        $message->update([
            'status' => WhatsAppMessageStatus::Failed,
            'failed_at' => now(),
            'error_message' => $errorMessage,
            'error_code' => $response->json('error.code'),
        ]);

        event(new WhatsAppMessageFailed($message->fresh()));
    }
}
