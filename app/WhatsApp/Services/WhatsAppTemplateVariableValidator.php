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
        $normalized = app(WhatsAppTemplateComponentBuilder::class)->normalizeVariables($variables);
        $missing = [];

        foreach ($required as $index => $label) {
            if (! array_key_exists($index, $normalized) || trim($normalized[$index]) === '') {
                $missing[] = $label;
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
        return app(WhatsAppTemplateComponentBuilder::class)->variableSlots($template);
    }
}
