<?php

namespace App\WhatsApp\DTOs;

use App\Models\Tenant\WhatsAppConversation;

class SendingPolicyResult
{
    public function __construct(
        public bool $allowed,
        public ?string $reason = null,
        public bool $mustUseTemplate = false,
        public ?WhatsAppConversation $targetConversation = null,
    ) {}

    public static function allow(WhatsAppConversation $conversation): self
    {
        return new self(
            allowed: true,
            targetConversation: $conversation,
        );
    }

    public static function deny(string $reason, bool $mustUseTemplate = false): self
    {
        return new self(
            allowed: false,
            reason: $reason,
            mustUseTemplate: $mustUseTemplate,
        );
    }
}
