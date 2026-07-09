<?php

namespace App\WhatsApp\DTOs;

class SyncWhatsAppTemplatesResult
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public array $errors = [],
    ) {}

    public function totalSynced(): int
    {
        return $this->created + $this->updated;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
