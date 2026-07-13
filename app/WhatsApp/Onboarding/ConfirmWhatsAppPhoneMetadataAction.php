<?php

namespace App\WhatsApp\Onboarding;

use App\Models\Tenant\WhatsAppNumber;
use App\WhatsApp\Services\WhatsAppCloudApiService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ConfirmWhatsAppPhoneMetadataAction
{
    public function __construct(
        protected WhatsAppCloudApiService $cloudApi,
    ) {}

    /**
     * Fetch WABA phone numbers and resolve which phone to activate.
     *
     * Rules:
     * - Prefer Embedded Signup phone_number_id when it matches a listed phone.
     * - Prefer Embedded Signup phone_number_id even if the list omits it (trust Meta session).
     * - If no preferred id and exactly one phone → use it.
     * - If no preferred id and multiple phones → awaiting_phone_selection (do not guess).
     */
    public function execute(
        string $accessToken,
        string $wabaId,
        ?string $preferredPhoneNumberId = null,
        ?WhatsAppNumber $numberForLogging = null,
    ): WhatsAppPhoneMetadataResult {
        if (blank($accessToken) || blank($wabaId)) {
            throw new RuntimeException('WABA id and access token are required to import phone metadata.');
        }

        $response = $numberForLogging !== null
            ? $this->cloudApi->listWabaPhoneNumbers($numberForLogging)
            : $this->cloudApi->listWabaPhoneNumbersWithToken($accessToken, $wabaId);

        if (! $response->successful()) {
            $message = $this->cloudApi->safeErrorMessage($response);

            Log::channel(config('whatsapp.log_channel'))->warning('WhatsApp phone_numbers list failed', [
                'waba_id' => $wabaId,
                'http_status' => $response->status(),
            ]);

            // If Embedded Signup already selected a phone, keep it and try a direct node read.
            if (filled($preferredPhoneNumberId) && $numberForLogging !== null) {
                return $this->enrichPreferredPhone($numberForLogging, $preferredPhoneNumberId, []);
            }

            if (filled($preferredPhoneNumberId)) {
                return new WhatsAppPhoneMetadataResult(
                    outcome: WhatsAppPhoneMetadataResult::CONFIRMED,
                    phoneNumberId: $preferredPhoneNumberId,
                    displayPhoneNumber: null,
                    verifiedName: null,
                    availablePhones: [],
                );
            }

            return new WhatsAppPhoneMetadataResult(
                outcome: WhatsAppPhoneMetadataResult::FAILED,
                error: $message,
            );
        }

        $phones = $this->normalizePhoneList($response->json('data', []));

        Log::channel(config('whatsapp.log_channel'))->info('WhatsApp phone_numbers listed', [
            'waba_id' => $wabaId,
            'count' => count($phones),
            'has_preferred' => filled($preferredPhoneNumberId),
        ]);

        if (filled($preferredPhoneNumberId)) {
            $matched = collect($phones)->firstWhere('id', $preferredPhoneNumberId);

            if ($matched !== null) {
                return new WhatsAppPhoneMetadataResult(
                    outcome: WhatsAppPhoneMetadataResult::CONFIRMED,
                    phoneNumberId: $preferredPhoneNumberId,
                    displayPhoneNumber: $matched['display_phone_number'],
                    verifiedName: $matched['verified_name'],
                    availablePhones: $phones,
                );
            }

            // Prefer Meta Embedded Signup selection over guessing among other listed phones.
            if ($numberForLogging !== null) {
                return $this->enrichPreferredPhone($numberForLogging, $preferredPhoneNumberId, $phones);
            }

            return new WhatsAppPhoneMetadataResult(
                outcome: WhatsAppPhoneMetadataResult::CONFIRMED,
                phoneNumberId: $preferredPhoneNumberId,
                displayPhoneNumber: null,
                verifiedName: null,
                availablePhones: $phones,
            );
        }

        if (count($phones) === 1) {
            $only = $phones[0];

            return new WhatsAppPhoneMetadataResult(
                outcome: WhatsAppPhoneMetadataResult::CONFIRMED,
                phoneNumberId: $only['id'],
                displayPhoneNumber: $only['display_phone_number'],
                verifiedName: $only['verified_name'],
                availablePhones: $phones,
            );
        }

        if (count($phones) === 0) {
            return new WhatsAppPhoneMetadataResult(
                outcome: WhatsAppPhoneMetadataResult::FAILED,
                error: 'No phone numbers were returned for this WABA.',
                availablePhones: [],
            );
        }

        return new WhatsAppPhoneMetadataResult(
            outcome: WhatsAppPhoneMetadataResult::AWAITING_SELECTION,
            availablePhones: $phones,
            error: 'Multiple phone numbers found; select one explicitly before continuing.',
        );
    }

    /**
     * @param  list<array{id: string, display_phone_number: ?string, verified_name: ?string}>  $availablePhones
     */
    protected function enrichPreferredPhone(
        WhatsAppNumber $number,
        string $preferredPhoneNumberId,
        array $availablePhones,
    ): WhatsAppPhoneMetadataResult {
        $response = $this->cloudApi->getPhoneNumber($number, $preferredPhoneNumberId);

        if ($response->successful()) {
            return new WhatsAppPhoneMetadataResult(
                outcome: WhatsAppPhoneMetadataResult::CONFIRMED,
                phoneNumberId: $preferredPhoneNumberId,
                displayPhoneNumber: $this->nullableString($response->json('display_phone_number')),
                verifiedName: $this->nullableString($response->json('verified_name')),
                availablePhones: $availablePhones,
            );
        }

        return new WhatsAppPhoneMetadataResult(
            outcome: WhatsAppPhoneMetadataResult::CONFIRMED,
            phoneNumberId: $preferredPhoneNumberId,
            displayPhoneNumber: null,
            verifiedName: null,
            availablePhones: $availablePhones,
        );
    }

    /**
     * @return list<array{id: string, display_phone_number: ?string, verified_name: ?string}>
     */
    protected function normalizePhoneList(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $phones = [];

        foreach ($data as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = $this->nullableString($row['id'] ?? null);

            if ($id === null) {
                continue;
            }

            $phones[] = [
                'id' => $id,
                'display_phone_number' => $this->nullableString($row['display_phone_number'] ?? null),
                'verified_name' => $this->nullableString($row['verified_name'] ?? null),
            ];
        }

        return $phones;
    }

    protected function nullableString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
