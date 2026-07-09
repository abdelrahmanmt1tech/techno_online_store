<?php

namespace App\WhatsApp\Enums;

enum WhatsAppApiRequestOperation: string
{
    case SendText = 'send_text';
    case SendTemplate = 'send_template';
    case HealthCheck = 'health_check';
    case ListTemplates = 'list_templates';

    public function label(): string
    {
        return match ($this) {
            self::SendText => __('dashboard.whatsapp_api_op_send_text'),
            self::SendTemplate => __('dashboard.whatsapp_api_op_send_template'),
            self::HealthCheck => __('dashboard.whatsapp_api_op_health_check'),
            self::ListTemplates => __('dashboard.whatsapp_api_op_list_templates'),
        };
    }
}
