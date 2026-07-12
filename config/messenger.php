<?php

return [
    'graph_api_version' => env('MESSENGER_GRAPH_API_VERSION', env('WHATSAPP_GRAPH_API_VERSION', 'v21.0')),
    'webhook_verify_token' => env('MESSENGER_WEBHOOK_VERIFY_TOKEN'),
    'app_secret' => env('META_APP_SECRET'),
    'allow_unsigned_webhooks' => env('MESSENGER_ALLOW_UNSIGNED_WEBHOOKS', false),
    'request_timeout' => (int) env('MESSENGER_REQUEST_TIMEOUT', 30),
    'log_channel' => env('MESSENGER_LOG_CHANNEL', 'stack'),
    'webhook_log_channel' => env('MESSENGER_WEBHOOK_LOG_CHANNEL', 'stack'),
    'webhook_payload_retention' => env('MESSENGER_WEBHOOK_PAYLOAD_RETENTION', 'minimized'),
    'customer_service_window_hours' => 24,
];
