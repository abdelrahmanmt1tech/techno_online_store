<?php

namespace App\WhatsApp\Enums;

enum WhatsAppConnectionMethod: string
{
    case ManualApiOnly = 'manual_api_only';
    case EmbeddedSignupApiOnly = 'embedded_signup_api_only';
    case EmbeddedSignupCoexistence = 'embedded_signup_coexistence';

    public function label(): string
    {
        return match ($this) {
            self::ManualApiOnly => __('dashboard.whatsapp_connection_method_manual_api_only'),
            self::EmbeddedSignupApiOnly => __('dashboard.whatsapp_connection_method_embedded_signup_api_only'),
            self::EmbeddedSignupCoexistence => __('dashboard.whatsapp_connection_method_embedded_signup_coexistence'),
        };
    }
}
