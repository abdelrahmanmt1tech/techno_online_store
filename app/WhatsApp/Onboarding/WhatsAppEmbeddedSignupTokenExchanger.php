<?php

namespace App\WhatsApp\Onboarding;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppEmbeddedSignupTokenExchanger
{
    /**
     * Exchange Embedded Signup token code for a business access token.
     * Uses META_APP_ID + META_APP_SECRET server-side only.
     */
    public function exchange(string $code): string
    {
        $appId = config('whatsapp.meta_app_id');
        $appSecret = config('whatsapp.app_secret');
        $version = config('whatsapp.graph_api_version', 'v21.0');

        if (blank($appId) || blank($appSecret)) {
            throw new RuntimeException('Meta App ID and App Secret must be configured for Embedded Signup.');
        }

        if (blank($code)) {
            throw new RuntimeException('Embedded Signup token code is missing.');
        }

        $response = Http::timeout((int) config('whatsapp.request_timeout', 30))
            ->asForm()
            ->get("https://graph.facebook.com/{$version}/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'code' => $code,
            ]);

        if ($response->failed()) {
            $this->logFailure($response);

            throw new RuntimeException($this->safeErrorMessage($response));
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Meta token exchange did not return an access token.');
        }

        Log::channel(config('whatsapp.log_channel'))->info('WhatsApp Embedded Signup token exchange ok', [
            'http_status' => $response->status(),
            'token_type' => $response->json('token_type'),
            'has_access_token' => true,
        ]);

        return $token;
    }

    protected function logFailure(Response $response): void
    {
        Log::channel(config('whatsapp.log_channel'))->warning('WhatsApp Embedded Signup token exchange failed', [
            'http_status' => $response->status(),
            'error_code' => $response->json('error.code'),
            'error_type' => $response->json('error.type'),
            'error_message' => $response->json('error.message'),
        ]);
    }

    protected function safeErrorMessage(Response $response): string
    {
        $message = $response->json('error.message', 'Token code exchange failed.');

        return is_string($message) ? $message : 'Token code exchange failed.';
    }
}
