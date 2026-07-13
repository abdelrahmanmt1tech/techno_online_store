<?php

namespace App\WhatsApp\Enums;

enum WhatsAppApiRequestOperation: string
{
    case SendText = 'send_text';
    case SendTemplate = 'send_template';
    case HealthCheck = 'health_check';
    case ListTemplates = 'list_templates';
    case SubscribeWabaApps = 'subscribe_waba_apps';
    case ListWabaPhoneNumbers = 'list_waba_phone_numbers';
    case GetPhoneNumber = 'get_phone_number';

    public function label(): string
    {
        return match ($this) {
            self::SendText => __('dashboard.whatsapp_api_op_send_text'),
            self::SendTemplate => __('dashboard.whatsapp_api_op_send_template'),
            self::HealthCheck => __('dashboard.whatsapp_api_op_health_check'),
            self::ListTemplates => __('dashboard.whatsapp_api_op_list_templates'),
            self::SubscribeWabaApps => __('dashboard.whatsapp_api_op_subscribe_waba_apps'),
            self::ListWabaPhoneNumbers => __('dashboard.whatsapp_api_op_list_waba_phone_numbers'),
            self::GetPhoneNumber => __('dashboard.whatsapp_api_op_get_phone_number'),
        };
    }
}
