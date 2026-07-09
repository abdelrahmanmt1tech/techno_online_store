<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppWebhookEvent;
use App\WhatsApp\Enums\WhatsAppWebhookProcessingStatus;
use App\WhatsApp\Jobs\ProcessWhatsAppWebhookJob;
use App\WhatsApp\Services\WhatsAppWebhookRequestLogger;
use App\WhatsApp\Services\WhatsAppWebhookSignatureVerifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request, WhatsAppWebhookRequestLogger $logger): Response
    {
        $mode = $request->query('hub.mode') ?? $request->query('hub_mode');
        $token = $request->query('hub.verify_token') ?? $request->query('hub_verify_token');
        $challenge = $request->query('hub.challenge') ?? $request->query('hub_challenge');

        $configuredToken = trim((string) config('whatsapp.webhook_verify_token'));
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
        WhatsAppWebhookSignatureVerifier $verifier,
        WhatsAppWebhookRequestLogger $logger,
    ): Response {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Hub-Signature-256');
        $secretConfigured = filled(config('whatsapp.app_secret'));

        if ($secretConfigured && ! $verifier->verify($rawBody, $signature)) {
            WhatsAppWebhookEvent::query()->create([
                'provider' => 'meta',
                'event_type' => 'invalid_signature',
                'processing_status' => WhatsAppWebhookProcessingStatus::Rejected,
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

        if (! $secretConfigured && ! config('whatsapp.allow_unsigned_webhooks', false)) {
            $logger->logReceiveAttempt($request, false, 403, 'unsigned_webhooks_disabled');

            return response('Forbidden', 403);
        }

        $payload = json_decode($rawBody, true) ?? [];
        $phoneNumberId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id');

        $event = WhatsAppWebhookEvent::query()->create([
            'provider' => 'meta',
            'event_type' => data_get($payload, 'entry.0.changes.0.field'),
            'phone_number_id' => $phoneNumberId,
            'processing_status' => WhatsAppWebhookProcessingStatus::Pending,
            'payload' => $payload,
            'signature_valid' => $secretConfigured ? true : null,
        ]);

        ProcessWhatsAppWebhookJob::dispatch($event->id);

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
