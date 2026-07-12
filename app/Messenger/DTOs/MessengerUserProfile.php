<?php

namespace App\Messenger\DTOs;

class MessengerUserProfile
{
    public function __construct(
        public ?string $profileName = null,
        public ?string $profilePictureUrl = null,
        public ?string $firstName = null,
        public ?string $lastName = null,
    ) {}

    public function hasDisplayName(): bool
    {
        return filled($this->profileName);
    }
}
