<?php

namespace App\Messenger\Onboarding;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MessengerFacebookLoginTokenExchanger
{
    public function exchangeCode(string $code): string
    {
        $version = config('messenger.graph_api_version', 'v21.0');
        $appId = config('messenger.meta_app_id');
        $appSecret = config('messenger.app_secret');
        $redirectUri = app(MessengerOnboardingStateService::class)->redirectUri();

        if (blank($appId) || blank($appSecret)) {
            throw new RuntimeException('META_APP_ID and META_APP_SECRET are required for Messenger Facebook Login.');
        }

        $response = Http::timeout((int) config('messenger.request_timeout', 30))
            ->asForm()
            ->get("https://graph.facebook.com/{$version}/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->safeErrorMessage($response));
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Messenger OAuth token exchange returned no access token.');
        }

        Log::channel(config('messenger.log_channel'))->info('Messenger Facebook Login code exchange ok', [
            'http_status' => $response->status(),
        ]);

        return $this->exchangeLongLivedUserToken($token);
    }

    public function exchangeLongLivedUserToken(string $shortLivedToken): string
    {
        $version = config('messenger.graph_api_version', 'v21.0');
        $appId = config('messenger.meta_app_id');
        $appSecret = config('messenger.app_secret');

        $response = Http::timeout((int) config('messenger.request_timeout', 30))
            ->asForm()
            ->get("https://graph.facebook.com/{$version}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortLivedToken,
            ]);

        if (! $response->successful()) {
            // Fall back to short-lived token if long-lived exchange is unavailable.
            Log::channel(config('messenger.log_channel'))->warning('Messenger long-lived user token exchange failed; using short-lived token', [
                'http_status' => $response->status(),
                'error_code' => $response->json('error.code'),
            ]);

            return $shortLivedToken;
        }

        $token = $response->json('access_token');

        return is_string($token) && $token !== '' ? $token : $shortLivedToken;
    }

    protected function safeErrorMessage(Response $response): string
    {
        $message = $response->json('error.message', 'Messenger OAuth token exchange failed.');

        Log::channel(config('messenger.log_channel'))->warning('Messenger OAuth token exchange failed', [
            'http_status' => $response->status(),
            'error_code' => $response->json('error.code'),
        ]);

        return is_string($message) ? $message : 'Messenger OAuth token exchange failed.';
    }
}
