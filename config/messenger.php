<?php

return [
    'graph_api_version' => env('MESSENGER_GRAPH_API_VERSION', env('WHATSAPP_GRAPH_API_VERSION', 'v21.0')),
    'webhook_verify_token' => env('MESSENGER_WEBHOOK_VERIFY_TOKEN'),
    'app_secret' => env('META_APP_SECRET'),
    'meta_app_id' => env('META_APP_ID'),
    'allow_unsigned_webhooks' => env('MESSENGER_ALLOW_UNSIGNED_WEBHOOKS', false),
    'request_timeout' => (int) env('MESSENGER_REQUEST_TIMEOUT', 30),
    'log_channel' => env('MESSENGER_LOG_CHANNEL', 'stack'),
    'webhook_log_channel' => env('MESSENGER_WEBHOOK_LOG_CHANNEL', 'messenger-webhook'),
    'webhook_payload_retention' => env('MESSENGER_WEBHOOK_PAYLOAD_RETENTION', 'minimized'),
    'customer_service_window_hours' => 24,

    /*
    | Facebook Login for Business — Messenger Page onboarding.
    | Runs on the central domain so tenant subdomains need not be listed in Meta Allowed Domains.
    */
    'facebook_login' => [
        'config_id' => env('MESSENGER_FACEBOOK_LOGIN_CONFIG_ID'),
        'redirect_uri' => env('MESSENGER_OAUTH_REDIRECT_URI'),
        'scopes' => env('MESSENGER_OAUTH_SCOPES', 'pages_show_list,pages_manage_metadata,pages_messaging'),
        'central_domain' => env('MESSENGER_ONBOARDING_CENTRAL_DOMAIN', env('WHATSAPP_EMBEDDED_SIGNUP_CENTRAL_DOMAIN', 'online-store.technomasrsystems.com')),
        'enforce_central_domain' => env('MESSENGER_ONBOARDING_ENFORCE_DOMAIN', true),
        'state_ttl_seconds' => (int) env('MESSENGER_ONBOARDING_STATE_TTL', 900),
        'subscribed_fields' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('MESSENGER_PAGE_SUBSCRIBED_FIELDS', 'messages,messaging_postbacks,message_deliveries,message_reads,messaging_seen')),
        ))),
    ],

    'onboarding' => [
        'session_retention_days' => (int) env('MESSENGER_ONBOARDING_SESSION_RETENTION_DAYS', 7),
    ],
];
