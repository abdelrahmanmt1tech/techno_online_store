<?php

namespace App\WhatsApp\Enums;

enum WhatsAppOnboardingStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case AwaitingPhoneSelection = 'awaiting_phone_selection';
    case SubscribingWebhooks = 'subscribing_webhooks';
    case Completed = 'completed';
    case Failed = 'failed';
    case Disconnected = 'disconnected';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => __('dashboard.whatsapp_onboarding_status_not_started'),
            self::InProgress => __('dashboard.whatsapp_onboarding_status_in_progress'),
            self::AwaitingPhoneSelection => __('dashboard.whatsapp_onboarding_status_awaiting_phone_selection'),
            self::SubscribingWebhooks => __('dashboard.whatsapp_onboarding_status_subscribing_webhooks'),
            self::Completed => __('dashboard.whatsapp_onboarding_status_completed'),
            self::Failed => __('dashboard.whatsapp_onboarding_status_failed'),
            self::Disconnected => __('dashboard.whatsapp_onboarding_status_disconnected'),
        };
    }
}
