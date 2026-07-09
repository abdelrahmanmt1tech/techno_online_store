<?php

namespace App\WhatsApp\Services;

use App\WhatsApp\Enums\WhatsAppTemplateCategory;
use App\WhatsApp\Enums\WhatsAppTemplateStatus;

class WhatsAppTemplatePayloadMapper
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     provider_template_id: ?string,
     *     name: string,
     *     language: string,
     *     category: WhatsAppTemplateCategory,
     *     status: WhatsAppTemplateStatus,
     *     components: array<int, mixed>,
     *     variables_schema: array<int, string>,
     *     raw_payload: array<string, mixed>,
     * }
     */
    public function mapFromMeta(array $payload): array
    {
        $components = $payload['components'] ?? [];

        if (! is_array($components)) {
            $components = [];
        }

        return [
            'provider_template_id' => isset($payload['id']) ? (string) $payload['id'] : null,
            'name' => (string) ($payload['name'] ?? ''),
            'language' => (string) ($payload['language'] ?? 'en'),
            'category' => $this->mapCategory($payload['category'] ?? null),
            'status' => $this->mapStatus($payload['status'] ?? null),
            'components' => $components,
            'variables_schema' => $this->deriveVariablesSchema($components),
            'raw_payload' => $payload,
        ];
    }

    public function mapStatus(?string $status): WhatsAppTemplateStatus
    {
        return match (strtoupper((string) $status)) {
            'APPROVED' => WhatsAppTemplateStatus::Approved,
            'PENDING' => WhatsAppTemplateStatus::Pending,
            'REJECTED' => WhatsAppTemplateStatus::Rejected,
            'PAUSED' => WhatsAppTemplateStatus::Paused,
            'DISABLED' => WhatsAppTemplateStatus::Disabled,
            default => WhatsAppTemplateStatus::Unknown,
        };
    }

    public function mapCategory(?string $category): WhatsAppTemplateCategory
    {
        return match (strtoupper((string) $category)) {
            'MARKETING' => WhatsAppTemplateCategory::Marketing,
            'AUTHENTICATION' => WhatsAppTemplateCategory::Authentication,
            default => WhatsAppTemplateCategory::Utility,
        };
    }

    /**
     * @param  array<int, mixed>  $components
     * @return array<int, string>
     */
    public function deriveVariablesSchema(array $components): array
    {
        $placeholders = [];

        foreach ($components as $component) {
            if (! is_array($component)) {
                continue;
            }

            $text = $component['text'] ?? '';

            if (! is_string($text) || $text === '') {
                continue;
            }

            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);

            foreach ($matches[1] as $match) {
                $placeholders[(int) $match] = '{{'.$match.'}}';
            }
        }

        ksort($placeholders);

        return array_values($placeholders);
    }
}
