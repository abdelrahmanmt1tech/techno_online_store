<?php

namespace App\WhatsApp\Enums;

enum WhatsAppTokenSource: string
{
    case Manual = 'manual';
    case EmbeddedSignup = 'embedded_signup';
    case SystemUser = 'system_user';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Manual => __('dashboard.whatsapp_token_source_manual'),
            self::EmbeddedSignup => __('dashboard.whatsapp_token_source_embedded_signup'),
            self::SystemUser => __('dashboard.whatsapp_token_source_system_user'),
            self::Unknown => __('dashboard.whatsapp_token_source_unknown'),
        };
    }
}
