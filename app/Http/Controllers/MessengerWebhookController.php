<?php

namespace App\Http\Controllers;

use App\Messenger\Enums\MessengerWebhookProcessingStatus;
use App\Messenger\Jobs\ProcessMessengerWebhookJob;
use App\Messenger\Services\MessengerWebhookInterpreter;
use App\Messenger\Services\MessengerWebhookRequestLogger;
use App\Messenger\Services\MessengerWebhookSignatureVerifier;
use App\Models\MessengerWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MessengerWebhookController extends Controller
{
    public function verify(Request $request, MessengerWebhookRequestLogger $logger): Response
    {
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');

        $configuredToken = trim((string) config('messenger.webhook_verify_token'));
        $accepted = $mode === 'subscribe' && hash_equals($configuredToken, trim((string) $token));

        if ($accepted) {
            $logger->logVerificationAttempt($request, $mode, $token, $challenge, true, 200);

            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        $logger->logVerificationAttempt($request, $mode, $token, $challenge, false, 403);

        return response('Forbidden', 403);
    }

    public function receive(
        Request $request,
        MessengerWebhookSignatureVerifier $verifier,
        MessengerWebhookRequestLogger $logger,
        MessengerWebhookInterpreter $interpreter,
    ): Response {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');
        $secretConfigured = filled(config('messenger.app_secret'));

        if ($secretConfigured && ! $verifier->verify($rawBody, $signature)) {
            $interpretation = $interpreter->interpret(null, 'invalid_signature', false);

            MessengerWebhookEvent::query()->create([
                'provider' => 'meta',
                'event_type' => 'invalid_signature',
                'summary' => $interpretation['summary'],
                'interpretation' => $interpretation,
                'processing_status' => MessengerWebhookProcessingStatus::Rejected,
                'signature_valid' => false,
                'diagnostic_data' => [
                    'has_signature' => filled($signature),
                    'content_length' => strlen($rawBody),
                ],
                'error_message' => 'Invalid webhook signature.',
                'processed_at' => now(),
            ]);

            $logger->logReceiveAttempt($request, false, 403, 'invalid_signature', false);

            return response('Forbidden', 403);
        }

        if (! $secretConfigured && ! config('messenger.allow_unsigned_webhooks', false)) {
            $logger->logReceiveAttempt($request, false, 403, 'unsigned_webhooks_disabled');

            return response('Forbidden', 403);
        }

        $payload = json_decode($rawBody, true) ?? [];
        $pageId = data_get($payload, 'entry.0.id');
        $eventType = data_get($payload, 'object');
        $interpretation = $interpreter->interpret(
            $payload,
            is_string($eventType) ? $eventType : null,
            $secretConfigured ? true : null,
        );

        $event = MessengerWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => is_string($eventType) ? $eventType : 'page',
            'summary' => $interpretation['summary'],
            'interpretation' => $interpretation,
            'page_id' => is_string($pageId) ? $pageId : null,
            'processing_status' => MessengerWebhookProcessingStatus::Pending,
            'payload' => $payload,
            'original_payload' => $payload,
            'signature_valid' => $secretConfigured ? true : null,
        ]);

        ProcessMessengerWebhookJob::dispatch($event->id);

        $logger->logReceiveAttempt(
            $request,
            true,
            200,
            'accepted',
            $secretConfigured ? true : null,
        );

        return response('OK', 200);
    }
}
