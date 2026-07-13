<?php

namespace App\WhatsApp\Services;

use App\Models\Tenant\WhatsAppApiRequest;
use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Enums\WhatsAppApiRequestOperation;
use App\WhatsApp\Enums\WhatsAppApiRequestOutcome;
use Illuminate\Http\Client\Response;

class WhatsAppApiRequestLogger
{
    protected ?int $lastLoggedRequestId = null;

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    public function log(
        WhatsAppNumber $number,
        WhatsAppApiRequestOperation $operation,
        array $requestPayload,
        Response $response,
        ?string $recipientPhone = null,
        int $durationMs = 0,
    ): WhatsAppApiRequest {
        $outcome = $response->successful()
            ? WhatsAppApiRequestOutcome::Success
            : WhatsAppApiRequestOutcome::Failed;

        $request = WhatsAppApiRequest::query()->create([
            'whatsapp_number_id' => $number->id,
            'operation' => $operation,
            'recipient_phone' => $recipientPhone,
            'http_status' => $response->status(),
            'api_error_code' => $this->stringify($response->json('error.code')),
            'outcome' => $outcome,
            'status_label' => $this->statusLabel($outcome, $response),
            'summary' => $this->summary($operation, $response, $recipientPhone, $requestPayload),
            'request_payload' => $requestPayload,
            'response_body' => $this->responseBody($response),
            'duration_ms' => $durationMs > 0 ? $durationMs : null,
        ]);

        $this->lastLoggedRequestId = $request->id;

        return $request;
    }

    public function getLastLoggedRequestId(): ?int
    {
        return $this->lastLoggedRequestId;
    }

    public function attachMessage(int $apiRequestId, int $messageId): void
    {
        WhatsAppApiRequest::query()
            ->whereKey($apiRequestId)
            ->update(['whatsapp_message_id' => $messageId]);
    }

    protected function statusLabel(WhatsAppApiRequestOutcome $outcome, Response $response): string
    {
        if ($outcome === WhatsAppApiRequestOutcome::Success) {
            return __('dashboard.whatsapp_api_status_success');
        }

        $code = $response->json('error.code');

        return match ($code) {
            190, 102 => __('dashboard.whatsapp_api_status_auth_error'),
            131047 => __('dashboard.whatsapp_api_status_re_engagement'),
            132000 => __('dashboard.whatsapp_api_status_template_param_count'),
            132012 => __('dashboard.whatsapp_api_status_template_param_format'),
            131026 => __('dashboard.whatsapp_api_status_undeliverable'),
            default => __('dashboard.whatsapp_api_status_failed', [
                'code' => $code ?? $response->status(),
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    protected function summary(
        WhatsAppApiRequestOperation $operation,
        Response $response,
        ?string $recipientPhone,
        array $requestPayload,
    ): string {
        $recipient = $recipientPhone ?? ($requestPayload['to'] ?? '—');

        if ($operation === WhatsAppApiRequestOperation::SendText) {
            $preview = (string) ($requestPayload['text']['body'] ?? '');

            return __('dashboard.whatsapp_api_summary_send_text', [
                'recipient' => $recipient,
                'preview' => mb_strimwidth($preview, 0, 80, '…'),
                'status' => $response->successful()
                    ? __('dashboard.whatsapp_api_outcome_success')
                    : __('dashboard.whatsapp_api_outcome_failed'),
            ]);
        }

        if ($operation === WhatsAppApiRequestOperation::SendTemplate) {
            $templateName = (string) ($requestPayload['template']['name'] ?? '—');

            return __('dashboard.whatsapp_api_summary_send_template', [
                'recipient' => $recipient,
                'template' => $templateName,
                'status' => $response->successful()
                    ? __('dashboard.whatsapp_api_outcome_success')
                    : __('dashboard.whatsapp_api_outcome_failed'),
            ]);
        }

        if ($operation === WhatsAppApiRequestOperation::HealthCheck) {
            return __('dashboard.whatsapp_api_summary_health_check', [
                'status' => $response->successful()
                    ? __('dashboard.whatsapp_api_outcome_success')
                    : __('dashboard.whatsapp_api_outcome_failed'),
            ]);
        }

        if ($operation === WhatsAppApiRequestOperation::SubscribeWabaApps) {
            return __('dashboard.whatsapp_api_summary_subscribe_waba_apps', [
                'waba' => (string) ($requestPayload['waba_id'] ?? '—'),
                'status' => $response->successful()
                    ? __('dashboard.whatsapp_api_outcome_success')
                    : __('dashboard.whatsapp_api_outcome_failed'),
            ]);
        }

        if ($operation === WhatsAppApiRequestOperation::ListWabaPhoneNumbers) {
            return __('dashboard.whatsapp_api_summary_list_waba_phone_numbers', [
                'waba' => (string) ($requestPayload['waba_id'] ?? '—'),
                'status' => $response->successful()
                    ? __('dashboard.whatsapp_api_outcome_success')
                    : __('dashboard.whatsapp_api_outcome_failed'),
            ]);
        }

        if ($operation === WhatsAppApiRequestOperation::GetPhoneNumber) {
            return __('dashboard.whatsapp_api_summary_get_phone_number', [
                'phone' => (string) ($requestPayload['phone_number_id'] ?? '—'),
                'status' => $response->successful()
                    ? __('dashboard.whatsapp_api_outcome_success')
                    : __('dashboard.whatsapp_api_outcome_failed'),
            ]);
        }

        return __('dashboard.whatsapp_api_summary_list_templates', [
            'status' => $response->successful()
                ? __('dashboard.whatsapp_api_outcome_success')
                : __('dashboard.whatsapp_api_outcome_failed'),
        ]);
    }

    protected function responseBody(Response $response): ?array
    {
        $json = $response->json();

        if (! is_array($json)) {
            return null;
        }

        $encoded = json_encode($json);

        if ($encoded !== false && strlen($encoded) > 16000) {
            return [
                'truncated' => true,
                'status' => $response->status(),
                'messages' => $response->json('messages'),
                'error' => $response->json('error'),
            ];
        }

        return $json;
    }

    protected function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
