<?php

namespace App\WhatsApp\Actions;

use App\Models\Tenant\WhatsAppMessage;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\DTOs\SendTemplateMessageData;
use App\WhatsApp\Enums\WhatsAppConnectionStatus;
use App\WhatsApp\Enums\WhatsAppMessageDirection;
use App\WhatsApp\Enums\WhatsAppMessageSenderType;
use App\WhatsApp\Enums\WhatsAppMessageStatus;
use App\WhatsApp\Enums\WhatsAppMessageType;
use App\WhatsApp\Events\WhatsAppMessageFailed;
use App\WhatsApp\Events\WhatsAppMessageSent;
use App\WhatsApp\Services\WhatsAppCloudApiService;
use App\WhatsApp\Services\WhatsAppSendingPolicyService;
use App\WhatsApp\Services\WhatsAppTemplateVariableValidator;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SendWhatsAppTemplateMessageAction
{
    public function __construct(
        protected WhatsAppSendingPolicyService $policy,
        protected WhatsAppCloudApiService $cloudApi,
        protected WhatsAppTemplateVariableValidator $variableValidator,
        protected SyncWhatsAppNumberRegistryAction $syncRegistry,
    ) {}

    public function execute(SendTemplateMessageData $data, ?Authenticatable $user = null, string $guard = 'tenant'): WhatsAppMessage
    {
        $policy = $this->policy->canSendTemplate($user, $data->whatsappNumber, $data->conversation, $data->template, $guard);

        if (! $policy->allowed) {
            throw new RuntimeException($policy->reason ?? 'Template sending not allowed.');
        }

        $validation = $this->variableValidator->validate($data->template, $data->variables);
        if (! $validation['valid']) {
            throw new RuntimeException(__('dashboard.whatsapp_template_variables_missing', [
                'placeholders' => implode(', ', $validation['missing']),
            ]));
        }

        $orderedVariables = array_values($data->variables);

        return DB::transaction(function () use ($data, $user, $orderedVariables) {
            $message = $data->conversation->messages()->create([
                'whatsapp_number_id' => $data->whatsappNumber->id,
                'direction' => WhatsAppMessageDirection::Outbound,
                'sender_type' => $user ? WhatsAppMessageSenderType::Agent : WhatsAppMessageSenderType::System,
                'type' => WhatsAppMessageType::Template,
                'template_id' => $data->template->id,
                'template_name' => $data->template->name,
                'template_language' => $data->template->language,
                'template_variables' => $data->variables,
                'status' => WhatsAppMessageStatus::Pending,
            ]);

            $response = $this->cloudApi->sendTemplate(
                $data->whatsappNumber,
                $data->conversation->customer_phone,
                $data->template->name,
                $data->template->language,
                $orderedVariables,
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
                'raw_payload' => ['request' => ['type' => 'template'], 'response' => $response->json()],
            ]);

            $preview = __('dashboard.whatsapp_template_message_preview', ['name' => $data->template->name]);
            $now = now();
            $data->conversation->update([
                'last_message_preview' => $preview,
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
        $errorMessage = app(WhatsAppCloudApiService::class)->safeErrorMessage($response);

        $numberUpdates = ['last_error_message' => $errorMessage];
        if (app(WhatsAppCloudApiService::class)->isAuthenticationError($response)) {
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
