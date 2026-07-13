<?php

namespace App\WhatsApp\Onboarding;

class WhatsAppPhoneMetadataResult
{
    public const CONFIRMED = 'confirmed';

    public const AWAITING_SELECTION = 'awaiting_phone_selection';

    public const FAILED = 'failed';

    /**
     * @param  list<array{id: string, display_phone_number: ?string, verified_name: ?string}>  $availablePhones
     */
    public function __construct(
        public string $outcome,
        public ?string $phoneNumberId = null,
        public ?string $displayPhoneNumber = null,
        public ?string $verifiedName = null,
        public ?string $error = null,
        public array $availablePhones = [],
    ) {}

    public function isConfirmed(): bool
    {
        return $this->outcome === self::CONFIRMED;
    }

    public function needsPhoneSelection(): bool
    {
        return $this->outcome === self::AWAITING_SELECTION;
    }
}
