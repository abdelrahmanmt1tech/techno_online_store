<?php

namespace App\Support\MetaReset;

/**
 * One Meta integration table entry for preview/reset.
 *
 * @phpstan-type TableDef array{
 *   channel: string,
 *   scope: string,
 *   table: string,
 *   priority: int,
 *   optional: bool,
 *   description: string,
 *   reason: string,
 *   may_contain_credentials: bool,
 * }
 */
final class MetaIntegrationResetTable
{
    public function __construct(
        public readonly string $channel,
        public readonly string $scope,
        public readonly string $table,
        public readonly int $priority,
        public readonly bool $optional,
        public readonly string $description,
        public readonly string $reason,
        public readonly bool $mayContainCredentials = false,
    ) {}

    /**
     * @return TableDef
     */
    public function toArray(): array
    {
        return [
            'channel' => $this->channel,
            'scope' => $this->scope,
            'table' => $this->table,
            'priority' => $this->priority,
            'optional' => $this->optional,
            'description' => $this->description,
            'reason' => $this->reason,
            'may_contain_credentials' => $this->mayContainCredentials,
        ];
    }
}
