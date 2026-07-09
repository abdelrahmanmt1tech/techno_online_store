<?php

namespace App\WhatsApp\Services;

use App\Models\Tenant\WhatsAppTemplate;

class WhatsAppTemplateVariableValidator
{
    /**
     * @param  array<string, string>  $variables
     * @return array{valid: bool, missing: array<int, string>}
     */
    public function validate(WhatsAppTemplate $template, array $variables): array
    {
        $required = $this->requiredPlaceholders($template);
        $missing = [];

        foreach ($required as $index => $placeholder) {
            $key = (string) $index;
            if (! array_key_exists($key, $variables) && ! array_key_exists($placeholder, $variables)) {
                $missing[] = $placeholder;
            }
        }

        return [
            'valid' => $missing === [],
            'missing' => $missing,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function requiredPlaceholders(WhatsAppTemplate $template): array
    {
        if (is_array($template->variables_schema) && $template->variables_schema !== []) {
            return array_values($template->variables_schema);
        }

        $placeholders = [];
        $components = $template->components ?? [];

        foreach ($components as $component) {
            $text = $component['text'] ?? '';
            if (! is_string($text)) {
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
