<?php

namespace App\Messenger\Services;

use App\Messenger\Enums\MessengerApiRequestOperation;
use App\Messenger\Enums\MessengerApiRequestOutcome;
use App\Models\Tenant\MessengerApiRequest;
use App\Models\Tenant\MessengerPage;
use Illuminate\Http\Client\Response;

class MessengerApiRequestLogger
{
    protected ?int $lastLoggedRequestId = null;

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    public function log(
        MessengerPage $page,
        MessengerApiRequestOperation $operation,
        array $requestPayload,
        Response $response,
        ?string $recipientPsid = null,
        int $durationMs = 0,
    ): MessengerApiRequest {
        $outcome = $response->successful()
            ? MessengerApiRequestOutcome::Success
            : MessengerApiRequestOutcome::Failed;

        $request = MessengerApiRequest::query()->create([
            'messenger_page_id' => $page->id,
            'operation' => $operation,
            'recipient_psid' => $recipientPsid,
            'http_status' => $response->status(),
            'api_error_code' => $this->stringify($response->json('error.code')),
            'outcome' => $outcome,
            'status_label' => $this->statusLabel($outcome, $response),
            'summary' => $this->summary($operation, $response, $recipientPsid, $requestPayload),
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
        MessengerApiRequest::query()
            ->whereKey($apiRequestId)
            ->update(['messenger_message_id' => $messageId]);
    }

    protected function statusLabel(MessengerApiRequestOutcome $outcome, Response $response): string
    {
        if ($outcome === MessengerApiRequestOutcome::Success) {
            return __('dashboard.messenger_api_status_success');
        }

        $code = $response->json('error.code');

        return match ($code) {
            190, 102 => __('dashboard.messenger_api_status_auth_error'),
            10 => __('dashboard.messenger_api_status_permission_error'),
            default => __('dashboard.messenger_api_status_failed', [
                'code' => $code ?? $response->status(),
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $requestPayload
     */
    protected function summary(
        MessengerApiRequestOperation $operation,
        Response $response,
        ?string $recipientPsid,
        array $requestPayload,
    ): string {
        $recipient = $recipientPsid
            ?? data_get($requestPayload, 'recipient.id')
            ?? '—';

        if ($operation === MessengerApiRequestOperation::SendText) {
            $preview = (string) data_get($requestPayload, 'message.text', '');

            return __('dashboard.messenger_api_summary_send_text', [
                'recipient' => $recipient,
                'preview' => mb_strimwidth($preview, 0, 80, '…'),
                'status' => $response->successful()
                    ? __('dashboard.messenger_api_outcome_success')
                    : __('dashboard.messenger_api_outcome_failed'),
            ]);
        }

        return __('dashboard.messenger_api_summary_generic', [
            'operation' => $operation->label(),
            'status' => $response->successful()
                ? __('dashboard.messenger_api_outcome_success')
                : __('dashboard.messenger_api_outcome_failed'),
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
                'message_id' => $response->json('message_id'),
                'recipient_id' => $response->json('recipient_id'),
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
