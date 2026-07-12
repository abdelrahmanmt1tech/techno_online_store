<?php

namespace App\Messenger\Services;

use App\Messenger\DTOs\MessengerUserProfile;
use App\Models\Tenant\MessengerPage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MessengerUserProfileService
{
    /**
     * Fetch Messenger user profile by PSID using the Page access token.
     * Returns null on any failure — callers must keep PSID fallback.
     */
    public function fetch(MessengerPage $page, string $psid): ?MessengerUserProfile
    {
        if (blank($page->page_access_token) || blank($psid)) {
            return null;
        }

        $version = config('messenger.graph_api_version');
        $timeout = (int) config('messenger.request_timeout', 30);

        try {
            $response = Http::timeout($timeout)
                ->withToken($page->page_access_token)
                ->get("https://graph.facebook.com/{$version}/{$psid}", [
                    'fields' => 'first_name,last_name,name,profile_pic',
                ]);
        } catch (Throwable $exception) {
            Log::channel(config('messenger.log_channel'))->warning('Messenger profile lookup request failed', [
                'page_id' => $page->page_id,
                'psid' => $psid,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }

        if ($response->failed()) {
            Log::channel(config('messenger.log_channel'))->info('Messenger profile lookup unsuccessful', [
                'page_id' => $page->page_id,
                'psid' => $psid,
                'http_status' => $response->status(),
                'error_code' => $response->json('error.code'),
                'error_message' => $response->json('error.message'),
            ]);

            return null;
        }

        $firstName = $this->nullableString($response->json('first_name'));
        $lastName = $this->nullableString($response->json('last_name'));
        $name = $this->nullableString($response->json('name'));
        $picture = $this->nullableString($response->json('profile_pic'));

        $profileName = $name;

        if (blank($profileName)) {
            $profileName = trim(implode(' ', array_filter([$firstName, $lastName])));
            $profileName = $profileName !== '' ? $profileName : null;
        }

        if (blank($profileName) && blank($picture)) {
            return null;
        }

        Log::channel(config('messenger.log_channel'))->info('Messenger profile lookup ok', [
            'page_id' => $page->page_id,
            'psid' => $psid,
            'http_status' => $response->status(),
            'has_name' => filled($profileName),
            'has_picture' => filled($picture),
        ]);

        return new MessengerUserProfile(
            profileName: $profileName,
            profilePictureUrl: $picture,
            firstName: $firstName,
            lastName: $lastName,
        );
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
