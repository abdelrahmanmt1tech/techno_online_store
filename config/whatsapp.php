<?php

return [
    'graph_api_version' => env('WHATSAPP_GRAPH_API_VERSION', 'v21.0'),
    'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    'app_secret' => env('META_APP_SECRET'),
    'meta_app_id' => env('META_APP_ID'),
    'allow_unsigned_webhooks' => env('WHATSAPP_ALLOW_UNSIGNED_WEBHOOKS', false),
    'default_locale' => env('WHATSAPP_DEFAULT_LOCALE', 'ar'),
    'request_timeout' => (int) env('WHATSAPP_REQUEST_TIMEOUT', 30),
    'log_channel' => env('WHATSAPP_LOG_CHANNEL', 'stack'),
    'webhook_log_channel' => env('WHATSAPP_WEBHOOK_LOG_CHANNEL', 'whatsapp-webhook'),
    'send_rate_limit' => (int) env('WHATSAPP_SEND_RATE_LIMIT', 30),
    'webhook_payload_retention' => env('WHATSAPP_WEBHOOK_PAYLOAD_RETENTION', 'minimized'),
    'customer_service_window_hours' => 24,

    /*
    | Embedded Signup runs only on the central domain so tenant subdomains
    | do not need to be listed in Meta Allowed Domains.
    */
    'embedded_signup' => [
        'config_id' => env('WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID'),
        'central_domain' => env('WHATSAPP_EMBEDDED_SIGNUP_CENTRAL_DOMAIN', 'online-store.technomasrsystems.com'),
        'enforce_central_domain' => env('WHATSAPP_EMBEDDED_SIGNUP_ENFORCE_DOMAIN', true),
        'state_ttl_seconds' => (int) env('WHATSAPP_ONBOARDING_STATE_TTL', 900),
    ],
];
