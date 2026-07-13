<?php

namespace App\Messenger\Enums;

enum MessengerApiRequestOperation: string
{
    case SendText = 'send_text';
    case SubscribePageApps = 'subscribe_page_apps';
    case ListManagedPages = 'list_managed_pages';

    public function label(): string
    {
        return match ($this) {
            self::SendText => __('dashboard.messenger_api_op_send_text'),
            self::SubscribePageApps => __('dashboard.messenger_api_op_subscribe_page_apps'),
            self::ListManagedPages => __('dashboard.messenger_api_op_list_managed_pages'),
        };
    }
}
