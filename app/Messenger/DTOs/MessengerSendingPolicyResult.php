<?php

namespace App\Messenger\DTOs;

use App\Models\Tenant\MessengerConversation;

class MessengerSendingPolicyResult
{
    public function __construct(
        public bool $allowed,
        public ?string $reason = null,
        public ?MessengerConversation $targetConversation = null,
    ) {}

    public static function allow(MessengerConversation $conversation): self
    {
        return new self(
            allowed: true,
            targetConversation: $conversation,
        );
    }

    public static function deny(string $reason): self
    {
        return new self(
            allowed: false,
            reason: $reason,
        );
    }
}
