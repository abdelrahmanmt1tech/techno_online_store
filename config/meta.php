<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta Integration Reset tool
    |--------------------------------------------------------------------------
    |
    | Destructive Admin maintenance tool. Must be explicitly enabled.
    | Does not call Meta Graph API. Does not delete tenants or non-Meta data.
    |
    */
    'integration_reset_enabled' => (bool) env('META_INTEGRATION_RESET_ENABLED', false),

    'integration_reset_permission' => 'meta.integrations.reset',

    'integration_reset_confirmation_phrase' => 'RESET META INTEGRATIONS',

    'integration_reset_preview_ttl_minutes' => 10,

    'integration_reset_lock_seconds' => 300,
];
