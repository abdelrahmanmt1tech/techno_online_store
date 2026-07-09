<?php

namespace App\WhatsApp\Services;

use App\Models\Tenant\WhatsAppTemplate;

class WhatsAppTemplateComponentBuilder
{
    /**
     * @param  array<int, mixed>  $components
     * @return array<int, string>
     */
    public function variableSlotsFromComponents(array $components): array
    {
        $slots = [];

        foreach ($this->orderedRawComponents($components) as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));
            $text = $component['text'] ?? '';

            if (! is_string($text) || $text === '') {
                continue;
            }

            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);

            if ($matches[1] === []) {
                continue;
            }

            $maxIndex = max(array_map('intval', $matches[1]));

            for ($index = 1; $index <= $maxIndex; $index++) {
                $slots[] = $type.' {{'.$index.'}}';
            }
        }

        return $slots;
    }

    /**
     * @return array<int, string>
     */
    public function variableSlots(WhatsAppTemplate $template): array
    {
        return $this->variableSlotsFromComponents($template->components ?? []);
    }

    /**
     * @param  array<int|string, string>  $variables
     * @return array<int, string>
     */
    public function normalizeVariables(array $variables): array
    {
        if ($variables === []) {
            return [];
        }

        if (array_is_list($variables)) {
            return array_map('strval', $variables);
        }

        $normalized = [];

        foreach ($variables as $key => $value) {
            if (is_numeric($key)) {
                $normalized[(int) $key] = (string) $value;
            }
        }

        if ($normalized === []) {
            return array_values($variables);
        }

        ksort($normalized, SORT_NUMERIC);

        return array_values($normalized);
    }

    /**
     * @param  array<int, string>  $orderedValues
     * @return array<int, array<string, mixed>>
     */
    public function buildApiComponents(WhatsAppTemplate $template, array $orderedValues): array
    {
        $components = [];
        $offset = 0;

        foreach ($this->orderedRawComponents($template->components ?? []) as $component) {
            $type = strtoupper((string) ($component['type'] ?? ''));
            $text = $component['text'] ?? '';

            if (! is_string($text) || $text === '') {
                continue;
            }

            preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);

            if ($matches[1] === []) {
                continue;
            }

            $maxIndex = max(array_map('intval', $matches[1]));
            $values = array_slice($orderedValues, $offset, $maxIndex);
            $offset += $maxIndex;

            if ($values === []) {
                continue;
            }

            $parameters = array_map(
                fn (string $value) => ['type' => 'text', 'text' => $value],
                $values,
            );

            $apiType = match ($type) {
                'HEADER' => 'header',
                'BODY' => 'body',
                default => null,
            };

            if ($apiType === null) {
                continue;
            }

            $components[] = [
                'type' => $apiType,
                'parameters' => $parameters,
            ];
        }

        return $components;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function orderedRawComponents(array $components): array
    {
        $order = ['HEADER' => 0, 'BODY' => 1, 'FOOTER' => 2, 'BUTTONS' => 3];

        usort($components, function (array $left, array $right) use ($order): int {
            $leftType = strtoupper((string) ($left['type'] ?? ''));
            $rightType = strtoupper((string) ($right['type'] ?? ''));

            return ($order[$leftType] ?? 99) <=> ($order[$rightType] ?? 99);
        });

        return $components;
    }
}
