<?php

return array (
  'nav' => 
  array (
    'pipeline' => 'Sales Pipeline',
    'messaging' => 'Messaging Inboxes',
    'channel_connections' => 'Channel Connections',
    'logs_diagnostics' => 'Logs & Diagnostics',
    'integrations' => 'Integrations',
    'settings' => 'CRM Settings',
    'commissions' => 'Commissions',
    'reports' => 'Reports',
  ),
  'channels' => 
  array (
    'nav' => 
    array (
      'whatsapp_logs' => 'WhatsApp Logs',
      'messenger_logs' => 'Messenger Logs',
      'instagram_logs' => 'Instagram Logs',
      'meta_webhook_events' => 'Meta Webhook Events',
      'whatsapp_connection' => 'WhatsApp Diagnostics',
      'messenger_connection' => 'Messenger Diagnostics',
      'instagram_connection' => 'Instagram Diagnostics',
      'instagram_integration' => 'Connect Instagram',
      'whatsapp_integration' => 'WhatsApp Integration',
      'connect_whatsapp' => 'Connect WhatsApp',
      'connect_messenger' => 'Connect Facebook Pages',
      'connect_instagram' => 'Connect Instagram',
      'connect_instagram_facebook_legacy' => 'Legacy: Instagram via Facebook Login',
      'facebook_connect' => 'Connect Facebook Pages',
      'conversation_inbox' => 'Unified Conversations Inbox',
      'whatsapp_inbox' => 'WhatsApp Inbox',
      'messenger_inbox' => 'Messenger Inbox',
      'instagram_inbox' => 'Instagram Inbox',
      'capture' => 'Lead Capture',
    ),
    'logs' => 
    array (
      'tabs' => 
      array (
        'webhooks' => 'Webhook Events',
        'api' => 'API Requests',
        'api_outbound' => 'API Requests (Outbound)',
      ),
      'columns' => 
      array (
        'received_at' => 'Received At',
        'event_type' => 'Event Type',
        'direction' => 'Direction',
        'summary' => 'Description',
        'processing' => 'Processing',
        'signature' => 'Signature',
        'client' => 'Client',
        'attempts' => 'Attempts',
        'duration_ms' => 'Duration (ms)',
        'time' => 'Time',
        'operation' => 'Operation',
        'method' => 'Method',
        'http' => 'HTTP',
        'error_code' => 'Error Code',
        'mid' => 'MID',
        'psid' => 'PSID',
        'igsid' => 'IGSID',
        'subtype' => 'Subtype',
        'account_ref' => 'Account / Page / Phone ID',
        'sender' => 'Sender',
        'failure_reason' => 'Failure / Note',
        'processed_at' => 'Processed At',
        'payload_shape' => 'Payload Shape',
        'event_source' => 'Event Source',
      ),
      'filters' => 
      array (
        'event_type' => 'Event Type',
        'payload_shape' => 'Payload Shape',
        'event_source' => 'Event Source',
        'processing_status' => 'Processing Status',
        'signature_valid' => 'Valid Signature',
        'received_at' => 'Received Date',
      ),
      'actions' => 
      array (
        'view' => 'Details',
        'reprocess' => 'Reprocess',
        'view_payload' => 'View Payload',
        'close' => 'Close',
      ),
      'modal' => 
      array (
        'api_details' => 'API Request Details',
      ),
      'notifications' => 
      array (
        'reprocess_scheduled' => 'Reprocessing scheduled',
        'reprocess_queued' => 'Reprocess queued',
      ),
      'empty' => 
      array (
        'webhooks_heading' => 'No webhook events',
        'api_heading' => 'No API requests',
        'webhooks_desc_whatsapp' => 'When notifications arrive from Meta they appear here with a simple summary.',
        'webhooks_desc_messenger' => 'When Messenger notifications arrive from Meta they appear here with a simple summary.',
        'webhooks_desc_instagram' => 'When Instagram notifications arrive from Meta they appear here with a simple summary.',
        'api_desc_whatsapp' => 'Send and sync requests to Meta are logged here.',
        'api_desc_messenger' => 'Send and profile-fetch requests to Meta are logged here.',
        'api_desc_instagram' => 'Send and profile-fetch requests to Instagram Graph are logged here.',
      ),
      'detail' => 
      array (
        'status' => 'Status',
        'direction' => 'Direction',
        'page' => 'Page',
        'psid' => 'PSID',
        'igsid' => 'IGSID',
        'mid' => 'MID',
        'signature' => 'Signature',
        'valid' => 'Valid',
        'invalid' => 'Invalid',
        'client' => 'Client',
        'conversation' => 'Conversation',
        'error' => 'Error',
        'instagram_account' => 'Instagram Account',
        'subtype' => 'Subtype',
        'messaging_event' => 'Messaging event:',
        'event_payload' => 'Event payload:',
        'parent_payload' => 'Full request (parent):',
      ),
    ),
    'templates' => 
    array (
      'sync' => 'Sync Templates',
      'sync_heading' => 'Sync WhatsApp Templates',
      'sync_desc' => 'Approved templates will be fetched from Meta Business and the local list updated.',
      'synced_title' => 'Templates synced',
      'synced_body' => 'Fetched :count templates from Meta.',
      'sync_failed' => 'Template sync failed',
    ),
    'instagram_connection' => 
    array (
      'title' => 'Instagram Connection Test',
      'check_failed_title' => 'Instagram connection check failed',
      'check_failed_body' => 'Status: :status — :error',
      'status_unknown' => 'unknown',
      'healthy_title' => 'Instagram connection healthy',
      'healthy_body' => 'Token valid — @:username (:account_id).',
      'mismatch_title' => 'Warning: token does not match the configured account',
      'mismatch_body' => 'Instagram ID from API: :api_id — META_INSTAGRAM_ACCOUNT_ID=:configured. The ID may differ from the Embedded Setup panel; use the value returned by /me.',
    ),
    'facebook_connect' => 
    array (
      'title' => 'Connect Facebook Page',
      'auth_completed_title' => 'Facebook authorization completed',
      'auth_completed_body' => 'Managed pages were loaded. Select one page to complete the App Review test.',
      'auth_failed_title' => 'Facebook authorization failed',
      'no_test_title' => 'No authorization test found',
      'no_test_body' => 'Connect Facebook Page first to load managed pages.',
      'invalid_selection_title' => 'Invalid page selection',
      'test_saved_title' => 'Test page saved',
      'test_saved_body' => 'This selection is stored for App Review only and does not change production Messenger credentials.',
      'status' => 
      array (
        'not_configured' => 'Not configured in .env',
        'connected' => 'Connected',
        'token_check_failed' => 'Configured but token check failed',
      ),
      'page' => 
      array (
        'production_heading' => 'Current Production Messenger Connection',
        'production_desc' => 'Read-only view of the live Messenger runtime. Production still uses <code>.env</code> credentials.',
        'current_page_id' => 'Current Page ID',
        'connection_status' => 'Connection status',
        'page_name_from_token' => 'Page name (from token check)',
        'auth_test_heading' => 'Facebook Page Authorization Test',
        'auth_test_desc' => 'OAuth test for <code>pages_show_list</code> and App Review only. Does not replace production credentials.',
        'connect_button' => 'Connect Facebook Page',
        'no_test_yet' => 'No authorization test yet. Click Connect to sign in to Meta and load managed pages.',
        'authorized_by' => 'Authorized by user #:id',
        'no_managed_pages' => 'Meta returned no managed pages for this authorization.',
        'save_selected' => 'Save selected page (test only)',
        'selected_test_page' => 'Selected test page',
        'matches_production' => 'Selected Page ID matches production configuration (:id).',
        'not_match_production' => 'Selected Page ID does not match production configuration (production: :id).',
      ),
    ),
    'capture' => 
    array (
      'filters' => 
      array (
        'channel' => 'Channel',
        'all_channels' => 'All channels',
        'lead_source' => 'Lead Source',
      ),
      'columns' => 
      array (
        'is_new' => 'New',
        'client' => 'Client',
        'source' => 'Source',
        'channel' => 'Channel',
        'phone_identity' => 'Phone / Identity',
        'last_message' => 'Last Message',
        'waiting_since' => 'Since',
      ),
      'action_capture' => 'Capture',
      'capture_heading' => 'Confirm lead capture',
      'capture_desc' => 'The lead will be assigned to you and the conversation opened to reply immediately.',
      'captured_title' => 'Lead captured',
      'capture_failed_title' => 'Could not capture the lead',
      'empty_heading' => 'No leads awaiting capture',
      'empty_desc' => 'When messages arrive from channels not linked to an employee they appear here.',
    ),
    'conversation_inbox' => 
    array (
      'sync_templates' => 'Sync WhatsApp templates',
      'sync_templates_heading' => 'Sync WhatsApp templates',
    ),
    'lead_clients' => 
    array (
      'add' => 'Add Lead',
      'add_heading' => 'Add Lead',
      'save' => 'Save',
      'cannot_add_title' => 'Cannot add the lead',
      'added_title' => 'Added successfully',
      'stage_change' => 'Change Clients Stage',
      'stage_change_submit' => 'Apply Change',
      'stage_change_desc' => 'Choose the new stage for the selected clients.',
      'stage_changed_title' => 'Clients stage updated',
    ),
    'whatsapp_integration' => 
    array (
      'title' => 'WhatsApp Integration',
      'config' => 
      array (
        'heading' => 'Configuration Status',
        'meta_app_id' => 'Meta App ID',
        'meta_app_secret' => 'Meta App Secret',
        'oauth_redirect_uri' => 'OAuth Redirect URI',
        'embedded_signup_config_id' => 'Embedded Signup Config ID',
        'embedded_signup_flow' => 'Embedded Signup Flow',
        'setup_mode' => 'Current WhatsApp Setup Mode',
        'callback_route' => 'Callback Route',
        'overall_readiness' => 'Overall Readiness',
        'configured' => 'Configured',
        'missing' => 'Missing',
        'ready' => 'Ready',
        'not_ready' => 'Not Ready',
        'missing_vars' => 'Missing required environment variables',
        'flow_standard' => 'Standard Embedded Signup',
        'flow_coexistence' => 'WhatsApp Business App Onboarding / Coexistence',
        'setup_mode_manual' => 'Manual',
        'setup_mode_embedded' => 'Embedded Signup',
        'coexistence_warning' => 'Coexistence requires a Meta Embedded Signup configuration that explicitly supports WhatsApp Business App onboarding. The CRM setting alone does not guarantee that the Meta configuration is enabled.',
      ),
      'connection' => 
      array (
        'heading' => 'Current WhatsApp Connection',
        'runtime_mode' => 'Runtime setup mode',
        'setup_source' => 'Setup source',
        'source_manual' => 'Manual (.env runtime)',
        'source_embedded' => 'Embedded Signup',
        'stored_heading' => 'Stored Embedded Signup account',
        'waba_id' => 'WABA ID',
        'phone_number_id' => 'Phone Number ID',
        'display_phone' => 'Display phone number',
        'verified_name' => 'Verified name',
        'connection_status' => 'Connection status',
        'business_portfolio_id' => 'Business Portfolio ID',
        'token_type' => 'Token type',
        'token_expiry' => 'Token expiry',
        'last_synced' => 'Last synced at',
        'encrypted_token_exists' => 'Encrypted token stored',
        'yes' => 'Yes',
        'no' => 'No',
        'no_stored' => 'No stored Embedded Signup connection was found.',
      ),
      'actions' => 
      array (
        'heading' => 'Embedded Signup',
        'start' => 'Start Embedded Signup',
        'loading' => 'Connecting…',
        'manual_mode_warning' => 'Completing Embedded Signup stores the authorized account, but it does not automatically change WHATSAPP_SETUP_MODE. The current manual WhatsApp connection will remain the active runtime source until the environment setting is changed manually.',
      ),
      'replace_modal' => 
      array (
        'heading' => 'Confirm WhatsApp account replacement',
        'body' => 'An active WhatsApp account is already stored in the CRM.',
        'warning' => 'A successful flow may update the stored WhatsApp record and deactivate other WhatsApp channel account rows in the database. This does not delete or disconnect anything at Meta, but it changes the internal connection record.',
        'type_phrase' => 'Type I UNDERSTAND to continue',
        'phrase_required' => 'You must type I UNDERSTAND exactly to continue.',
        'cancel' => 'Cancel',
        'continue' => 'Continue to Meta',
      ),
      'review_helper' => 
      array (
        'heading' => 'App Review Helper',
        'intro' => 'This page allows an authorized FlyAram administrator to start Meta-hosted WhatsApp onboarding, authorize access to FlyAram business assets, and verify the WhatsApp Business Account and phone number stored by the CRM.',
        'no_secrets' => 'No access tokens, app secrets, or authorization headers are displayed on this page.',
        'hosted_test_link' => 'Open Meta-hosted Embedded Signup test page',
      ),
      'notifications' => 
      array (
        'success_title' => 'WhatsApp account connected successfully',
        'success_body' => 'The account was stored securely. The current runtime mode has not been changed automatically.',
        'success_body_v4' => 'The authorized WhatsApp Business Account and phone number were stored securely. The current runtime mode remains Manual.',
        'cancelled_title' => 'WhatsApp onboarding was cancelled',
        'cancelled_body' => 'No runtime configuration was changed.',
        'confirm_replace_title' => 'Active WhatsApp account already exists',
        'confirm_replace_body' => 'Return to the integration page and explicitly confirm before starting a replacement flow.',
        'failed_title' => 'WhatsApp onboarding could not be completed',
        'failed_body' => 'Review the application logs for the sanitized technical reason.',
        'invalid_session_title' => 'Embedded Signup session invalid',
        'invalid_session_body' => 'The Embedded Signup session is invalid or has expired. Please start the process again.',
        'asset_mismatch_title' => 'WhatsApp account validation failed',
        'asset_mismatch_body' => 'The WhatsApp account information returned by Meta could not be validated.',
        'config_incomplete_title' => 'Meta configuration incomplete',
        'config_incomplete_body' => 'Complete the required environment variables before starting Embedded Signup.',
      ),
    ),
    'connect_whatsapp' => 
    array (
      'title' => 'Connect WhatsApp',
      'intro' => 'Choose an independent connection path. Each path uses its own Config ID and never falls back to another.',
      'manual_title' => 'Manual API Connection',
      'manual_body' => 'For admin, support, and migration. Paste Cloud API credentials and validate against Graph.',
      'manual_point_1' => 'Validates WABA + Phone Number ID ownership',
      'manual_point_2' => 'Stores the token encrypted in the tenant database only',
      'manual_point_3' => 'Syncs the central registry without tokens',
      'manual_cta' => 'Connect manually',
      'manual_success' => 'WhatsApp connected manually',
      'manual_failed' => 'Manual connection failed',
      'recommended' => 'Recommended',
      'api_only_title' => 'API Only via Embedded Signup',
      'api_only_body' => 'Connect a Cloud API number through Meta Embedded Signup (no Business App coexistence).',
      'api_only_point_1' => 'Uses WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID',
      'api_only_point_2' => 'Does not send whatsapp_business_app_onboarding',
      'api_only_point_3' => 'Supports multi-phone selection without guessing',
      'api_only_cta' => 'Start API Only',
      'api_only_config_missing_title' => 'API Only Config ID missing',
      'api_only_config_missing_body' => 'Set WHATSAPP_EMBEDDED_SIGNUP_CONFIG_ID (and Meta App ID/Secret) to enable this card.',
      'coexistence_title' => 'WhatsApp Business App + Cloud API',
      'coexistence_body' => 'Coexistence onboarding keeps the Business App working on the same number while enabling Cloud API.',
      'coexistence_point_1' => 'Uses WHATSAPP_EMBEDDED_SIGNUP_COEXISTENCE_CONFIG_ID',
      'coexistence_point_2' => 'Sends featureType=whatsapp_business_app_onboarding',
      'coexistence_point_3' => 'Safe disconnect never deregisters Meta assets',
      'coexistence_cta' => 'Start Coexistence',
      'coexistence_unavailable_cta' => 'Coexistence unavailable',
      'coexistence_config_missing_title' => 'Coexistence Config ID missing',
      'coexistence_config_missing_body' => 'Set WHATSAPP_EMBEDDED_SIGNUP_COEXISTENCE_CONFIG_ID to enable this card. The API Only Config ID is never used as a fallback.',
      'config_required' => 'Config required',
      'accounts_heading' => 'Connected WhatsApp accounts',
      'no_accounts' => 'No WhatsApp accounts connected yet.',
      'sessions_heading' => 'Onboarding sessions',
      'no_sessions' => 'No recent onboarding sessions.',
      'disconnected' => 'WhatsApp disconnected from the platform',
      'test_ok' => 'Connection test passed',
      'test_failed' => 'Connection test failed',
      'sync_ok' => 'Metadata synced',
      'sync_failed' => 'Metadata sync failed',
      'es_success' => 'Embedded Signup completed',
      'es_failed' => 'Embedded Signup failed',
      'reconnect_manual_hint' => 'Update credentials in the Manual card to reconnect a manual account.',
      'set_default' => 'Set as default number',
      'default_set' => 'Default WhatsApp number updated',
      'add_another' => 'Add another number',
    ),
    'connect_messenger' => 
    array (
      'title' => 'Connect Messenger',
      'intro' => 'Connect one or more Facebook Pages. OAuth is the merchant path; Manual stays available for admin and support.',
      'recommended' => 'Recommended for merchants',
      'oauth_title' => 'Connect with Facebook',
      'oauth_body' => 'Facebook Login, choose pages explicitly, store Page Access Tokens in the tenant database only.',
      'oauth_cta' => 'Connect with Facebook',
      'oauth_config_missing_title' => 'Facebook Login not configured',
      'oauth_config_missing_body' => 'Set META App ID/Secret and META_MESSENGER_OAUTH_REDIRECT_URI to enable this card.',
      'oauth_failed' => 'Facebook Login failed to start',
      'manual_title' => 'Manual Page Connection',
      'manual_body' => 'For admin, support, and migration. Paste Page ID + Page Access Token and validate against Graph.',
      'manual_cta' => 'Connect manually',
      'manual_success' => 'Messenger page connected manually',
      'manual_failed' => 'Manual connection failed',
      'set_default' => 'Set as default page',
      'picker_title' => 'Select Facebook Pages',
      'picker_body' => 'Only explicitly selected pages will be connected. Tokens are cleared after finalization.',
      'picker_empty' => 'No pages available in this session. Restart Facebook Login.',
      'picker_cta' => 'Connect selected pages',
      'pages_connected' => 'Messenger pages connected',
      'pages_failed' => 'Page connection failed',
      'connected_title' => 'Connected Facebook Pages',
      'connected_empty' => 'No Messenger pages connected yet.',
      'default_badge' => 'Default',
      'default_set' => 'Default page updated',
      'disconnected' => 'Messenger page disconnected from the platform',
      'reconnect_manual_hint' => 'Update the Page Access Token in the Manual card to reconnect.',
      'test_ok' => 'Connection test passed',
      'test_failed' => 'Connection test failed',
      'sync_ok' => 'Metadata synced',
      'sync_failed' => 'Metadata sync failed',
      'resubscribe_ok' => 'Webhook subscription refreshed',
      'resubscribe_failed' => 'Webhook resubscribe failed',
      'action_default' => 'Set default',
      'action_test' => 'Test',
      'action_sync' => 'Sync metadata',
      'action_resubscribe' => 'Resubscribe',
      'action_reconnect' => 'Reconnect',
      'action_disconnect' => 'Disconnect',
    ),
    'connect_instagram' => 
    array (
      'title' => 'Connect Instagram (Legacy Facebook Login)',
      'legacy_title' => 'Legacy: Instagram via Facebook Login',
      'intro' => 'LEGACY path. Prefer Connect Instagram (Instagram Login) for App Review and production. This page uses Facebook Login + Page Access Tokens.',
      'recommended' => 'Legacy / rollback only',
      'oauth_title' => 'Connect with Facebook (Legacy)',
      'oauth_body' => 'Facebook Login, choose Instagram accounts explicitly, store Page Access Tokens in the tenant database only.',
      'oauth_cta' => 'Connect with Facebook (Legacy)',
      'oauth_config_missing_title' => 'Facebook Login not configured',
      'oauth_config_missing_body' => 'Set META App ID/Secret and META_INSTAGRAM_FACEBOOK_LOGIN_REDIRECT_URI to enable this card.',
      'oauth_failed' => 'Facebook Login failed to start',
      'manual_title' => 'Manual Instagram Connection (Legacy)',
      'manual_body' => 'For admin, support, and migration. Paste Instagram Account ID + Page Access Token (Page ID optional) and validate against Graph.',
      'manual_cta' => 'Connect manually',
      'manual_success' => 'Instagram account connected manually',
      'manual_failed' => 'Manual connection failed',
      'set_default' => 'Set as default account',
      'picker_title' => 'Select Instagram Accounts',
      'picker_body' => 'Only explicitly selected accounts will be connected. Tokens are cleared after finalization.',
      'picker_empty' => 'No Instagram accounts available in this session. Restart Facebook Login.',
      'picker_cta' => 'Connect selected accounts',
      'accounts_connected' => 'Instagram accounts connected',
      'accounts_failed' => 'Account connection failed',
      'connected_title' => 'Connected Instagram Accounts',
      'connected_empty' => 'No Instagram accounts connected yet.',
      'default_badge' => 'Default',
      'default_set' => 'Default account updated',
      'disconnected' => 'Instagram account disconnected from the platform',
      'reconnect_manual_hint' => 'Update the Page Access Token in the Manual card to reconnect.',
      'test_ok' => 'Connection test passed',
      'test_failed' => 'Connection test failed',
      'sync_ok' => 'Metadata synced',
      'sync_failed' => 'Metadata sync failed',
      'resubscribe_ok' => 'Webhook subscription refreshed',
      'resubscribe_failed' => 'Webhook resubscribe failed',
      'action_default' => 'Set default',
      'action_test' => 'Test',
      'action_sync' => 'Sync metadata',
      'action_resubscribe' => 'Resubscribe',
      'action_reconnect' => 'Reconnect',
      'action_disconnect' => 'Disconnect',
    ),
    'instagram_integration' => 
    array (
      'title' => 'Connect Instagram',
      'review_helper' => 
      array (
        'heading' => 'App Review screencast helper',
        'intro' => 'Record these steps in English to demonstrate the complete Instagram Login flow and end-to-end messaging.',
        'step_connect' => 'Click Connect Instagram and sign in with the Instagram Business account.',
        'step_grant' => 'Grant instagram_business_basic and instagram_business_manage_messages (pages_read_engagement is not requested).',
        'step_verify' => 'Return to this page and confirm the connected username and account ID.',
        'step_token_check' => 'Run connection check (GET /me via server-side token).',
        'step_send_test' => 'Send a test message from CRM to a tester IGSID.',
        'step_native_client' => 'Show the message in the native Instagram client.',
        'step_webhooks' => 'Open Meta Webhook Events and show received webhook rows (message or read receipt).',
      ),
      'connection' => 
      array (
        'heading' => 'Connection status',
        'desc' => 'Connect via Instagram Login. The Instagram User Access Token is stored encrypted on the server and never displayed. Messaging uses graph.instagram.com.',
      ),
      'managed_account' => 
      array (
        'heading' => 'Managed Instagram business account',
        'desc' => 'Only the managed business account @fly_aram should be connected to this CRM. Personal Meta tester accounts are for sending test DMs in the native Instagram app only.',
        'managed_username' => 'Managed account',
        'professional_id' => 'Professional account ID (webhooks & messaging API)',
        'app_scoped_id' => 'App-scoped OAuth ID (internal only — not used for webhooks)',
        'tester_heading' => 'Tester accounts (do not connect to CRM)',
        'tester_points' => 
        array (
          0 => 'Personal tester accounts may appear in Meta Dashboard → Generate access tokens — ignore them for CRM integration.',
          1 => 'Do not generate tokens or enable Webhook Subscription for personal tester accounts.',
          2 => 'Use tester accounts only to send/receive test DMs to @fly_aram in the native Instagram client.',
          3 => 'Never use a tester account ID as sender ID, recipient ID, or CRM connection ID.',
        ),
        'webhook_id_note' => 'Webhook entry.id and recipient.id use the professional account ID (often starting with 178414…). This must match the ID stored after Connect Instagram.',
        'reconnect_mismatch' => 'If the account ID here does not match Meta Dashboard for @fly_aram, click Reconnect Instagram to refresh the professional account ID.',
      ),
      'diagnostics' => 
      array (
        'heading' => 'Diagnostics',
        'desc' => 'Verify the active token and optionally send a test DM for App Review.',
      ),
      'fields' => 
      array (
        'status' => 'Status',
        'token_source' => 'Connection source',
        'username' => 'Instagram username',
        'account_id' => 'Instagram account ID (/me user_id)',
        'account_type' => 'Account type',
        'token_expiry' => 'Token expiry',
        'last_checked' => 'Last token check',
        'last_webhook' => 'Last webhook received',
        'granted_scopes' => 'Granted / requested scopes',
        'test_recipient' => 'Tester IGSID (recipient)',
      ),
      'status' => 
      array (
        'connected' => 'Connected via OAuth',
        'not_connected' => 'Not connected — using ENV fallback if configured',
      ),
      'token_source' => 
      array (
        'db' => 'OAuth Database Connection',
        'env' => 'Legacy ENV fallback',
      ),
      'notes' => 
      array (
        'env_fallback' => 'This connection is using the legacy ENV fallback. For App Review and production usage, connect Instagram through the Connect Instagram button.',
      ),
      'actions' => 
      array (
        'connect' => 'Connect Instagram',
        'reconnect' => 'Reconnect Instagram',
        'disconnect' => 'Disconnect',
        'disconnect_confirm' => 'Disconnect the OAuth token? Webhook logs are kept. ENV fallback will be used if configured.',
        'run_check' => 'Run connection check',
        'send_test' => 'Send test message',
        'webhook_diagnostics' => 'Open webhook diagnostics',
      ),
      'notifications' => 
      array (
        'connect_success_title' => 'Instagram connected',
        'connect_success_body' => 'The OAuth token was stored securely. Run connection check to verify.',
        'connect_failed_title' => 'Instagram connection failed',
        'disconnect_success_title' => 'Instagram disconnected',
        'check_success_title' => 'Connection check passed',
        'check_failed_title' => 'Connection check failed',
        'test_missing_recipient' => 'Enter a tester IGSID first.',
        'test_sent_title' => 'Test message queued',
        'test_sent_body' => 'Check the native Instagram client for the tester account.',
        'test_failed_title' => 'Test message failed',
      ),
    ),
    'instagram_connection_page' => 
    array (
      'title' => 'Instagram Diagnostics',
      'subtitle' => 'Legacy connection verification and migration diagnostics. For App Review, use Instagram Integration.',
      'primary_path_heading' => 'Primary connection path: Instagram Integration',
      'primary_path_body' => 'Connect, reconnect, and demonstrate Instagram for Meta App Review on the Instagram Integration page. This diagnostics page is not the primary connection flow.',
      'primary_path_link' => 'Open Instagram Integration',
      'legacy_notice_heading' => 'Legacy diagnostics only',
      'legacy_notice_body' => 'This page helps verify the active server-side token and troubleshoot migration from legacy ENV configuration.',
      'legacy_notice_points' => 
      array (
        0 => 'The CRM uses the OAuth database connection first when one exists in crm_instagram_connections.',
        1 => 'Legacy ENV fallback (META_INSTAGRAM_ACCESS_TOKEN and META_INSTAGRAM_ACCOUNT_ID) is supported only for diagnostics or migration — not for App Review.',
        2 => 'For App Review screencasts, use Connect Instagram on the Instagram Integration page.',
        3 => 'Access tokens are never displayed in the UI or written to application logs.',
      ),
      'connection_source' => 'Connection source',
      'token_source' => 
      array (
        'db' => 'OAuth Database Connection',
        'env' => 'Legacy ENV fallback',
        'none' => 'Not configured',
      ),
      'legacy_env_warning' => 'This connection is using the legacy ENV fallback. For App Review and production usage, connect Instagram through the Connect Instagram button.',
      'id_types_heading' => 'Instagram ID types',
      'id_types_body' => 'Meta returns two IDs for Instagram Login. The CRM stores the professional account ID (user_id) — the same ID used in webhook entry.id and POST /{id}/messages. The app-scoped id from /me is not used for webhooks.',
      'managed_account_note' => 'Managed business account only: @fly_aram. Do not generate tokens or enable webhooks for personal tester accounts listed in Meta Dashboard.',
      'oauth_db_status' => 'OAuth database connection',
      'oauth_connected_yes' => 'Active OAuth connection stored',
      'oauth_connected_no' => 'No OAuth connection — ENV fallback or not configured',
      'env_fallback_configured' => 'Legacy ENV fallback configured',
      'env_fallback_yes' => 'Yes (diagnostics / migration only)',
      'env_fallback_no' => 'No',
      'configured_assets_heading' => 'Active connection summary',
      'configured_assets_desc' => 'Read-only summary of the token source and account identifiers used by the CRM runtime.',
      'verification_heading' => 'Connection check (diagnostics)',
      'verification_results_heading' => 'Latest check result',
      'instagram_business_account_id' => 'Instagram account ID (/me user_id)',
      'username' => 'Username',
      'account_type' => 'Account Type',
      'account_type_pending' => 'Run connection check to retrieve from Instagram API',
      'app_id' => 'App ID',
      'integration_status' => 'Integration status',
      'config_match' => 'Account ID match',
      'not_yet_verified' => 'Not verified yet',
      'verification_failed' => 'Verification failed',
      'last_check_time' => 'Last check time',
      'last_check_pending' => 'No check run yet',
      'display_name' => 'Display name',
      'profile_picture' => 'Profile picture',
      'current_config' => 'Current configuration',
      'status' => 'Status',
      'enabled' => 'Enabled',
      'disabled' => 'Disabled',
      'account_id_label' => 'Account ID (account IGSID)',
      'token_not_shown' => 'The access token is never displayed on this page for security reasons.',
      'token_check' => 'Connection check',
      'token_check_desc' => 'Click <strong>Run connection check</strong> to call <code class="text-xs">GET /me</code> on graph.instagram.com using the active server token. The CRM uses the <strong>OAuth database connection first</strong>; legacy ENV variables are used only when no OAuth connection exists (diagnostics / migration).',
      'run_check' => 'Run connection check',
      'connection_status' => 'Connection status',
      'success' => 'Success',
      'failed' => 'Failed',
      'match_config' => 'Config match',
      'yes' => 'Yes',
      'no' => 'No',
      'profile_picture_na' => 'Not available',
      'stored_id_note' => 'Configured ID in environment: :id — it may differ from the Embedded Setup panel; rely on the value returned by /me.',
      'error' => 'Error',
      'last_check' => 'Last check',
    ),
    'inbox' => 
    array (
      'search' => 
      array (
        'whatsapp' => 'Search by name or phone number',
        'messenger' => 'Search by name or PSID',
        'instagram' => 'Search by name, @username or IGSID',
      ),
      'filters' => 
      array (
        'unassigned' => 'Unassigned',
        'mine' => 'My chats',
        'mine_alt' => 'Assigned to me',
        'unread' => 'Unread',
        'closed' => 'Closed',
        'open' => 'Open',
      ),
      'no_messages' => 'No messages yet',
      'empty_list' => 
      array (
        'whatsapp' => 'No conversations in this category.',
        'messenger' => 'No Messenger conversations in this category.',
        'instagram' => 'No Instagram conversations.',
      ),
      'assignee' => 'Assignee:',
      'unassigned_value' => 'Unassigned',
      'claim' => 'Claim conversation',
      'claim_short' => 'Claim',
      'close' => 'Close',
      'reopen' => 'Reopen',
      'send' => 'Send',
      'window_expired' => 'The 24-hour window has expired. Free-form text messages can no longer be sent — choose an approved template below.',
      'template_select_placeholder' => '— Send an approved WhatsApp template —',
      'no_templates_notice' => 'No approved templates locally. Use the “Sync WhatsApp templates” button at the top of the page to fetch them from Meta.',
      'composer' => 
      array (
        'whatsapp' => 'Type your message...',
        'whatsapp_disabled' => 'Free-form messages are disabled outside the 24-hour window',
        'messenger' => 'Type a Messenger message...',
        'instagram' => 'Type an Instagram message...',
      ),
      'placeholder' => 
      array (
        'select_title' => 'Select a conversation to start',
        'select_desc' => 'Pick a conversation from the sidebar to view messages and reply.',
        'messenger' => 'Select a Messenger conversation to view messages.',
        'instagram' => 'Select an Instagram conversation.',
      ),
      'external_send' => 'Sent from Business Suite',
      'sidebar' => 
      array (
        'client' => 'Client',
        'stage' => 'Stage',
        'lead_source' => 'Lead source',
        'psid_admin' => 'PSID (admin)',
        'last_activity' => 'Last activity',
        'messenger_profile_status' => 'Messenger profile status',
        'profile_status' => 'Profile status',
        'phone' => 'Phone',
        'synced_ago' => 'Synced :time',
        'sync_failed' => 'Sync failed — showing a fallback name',
        'not_synced' => 'Not synced yet',
        'refresh_messenger_profile' => 'Refresh Messenger profile',
        'check_messenger_connection' => 'Check Messenger connection',
        'refresh_profile' => 'Refresh profile',
        'phone_note' => 'Phone and WhatsApp numbers are not available automatically from Messenger.',
        'suggested_phone' => 'Detected a possible number: :phone',
        'confirm_phone' => 'Confirm & save number',
        'saved_phone' => 'Saved number: :phone',
        'request_phone' => 'Request phone number from the customer',
        'open_client' => 'Open client profile',
        'details_here' => 'Client details appear here.',
      ),
      'crm_context' => 
      array (
        'title' => 'CRM context',
        'client' => 'Client',
        'provisional' => 'Provisional',
        'reply_from' => 'Reply from',
        'assigned' => 'Assigned',
        'branch' => 'Branch',
        'opportunities' => 'Open opportunities',
        'identity' => 'Identity',
        'phone' => 'Phone',
        'username' => 'Username',
        'none' => '—',
        'assign_to_me' => 'Assign to me',
        'unassign' => 'Unassign',
        'create_opportunity' => 'Create opportunity',
        'link_customer' => 'Link customer',
        'merge_provisional' => 'Merge provisional',
        'client_id_placeholder' => 'Client ID',
        'target_client_id_placeholder' => 'Target client ID',
        'opportunity_title_placeholder' => 'Opportunity title (optional)',
        'submit_link' => 'Link',
        'submit_merge' => 'Merge',
        'submit_opportunity' => 'Create',
        'success' => 
        array (
          'linked' => 'Customer linked to conversation',
          'merged' => 'Provisional client merged',
          'assigned_me' => 'Conversation assigned to you',
          'assigned' => 'Conversation assigned',
          'unassigned' => 'Conversation unassigned',
          'opportunity_created' => 'Opportunity created from conversation',
        ),
        'errors' => 
        array (
          'unauthorized' => 'You are not allowed to perform this action',
          'client_not_found' => 'Client not found',
          'user_not_found' => 'Employee not found',
          'identity_missing' => 'No contact identity found for this conversation',
          'not_provisional' => 'Current client is not provisional',
          'subscription_blocked' => 'Channel subscription or feature is inactive',
          'no_conversation' => 'No conversation selected',
        ),
      ),
    ),
  ),
  'actions' => 
  array (
    'view_client' => 'View Client',
    'change_stage' => 'Change stage',
    'updated' => 'Updated',
    'close' => 'Close',
    'edit_client' => 'Edit Client',
    'save' => 'Save',
    'manage_opportunities' => 'Manage Opportunities',
    'open_latest_opportunity' => 'Open Latest Opportunity',
    'create_opportunity' => 'Create Opportunity',
    'view_open_opportunities' => 'View Open Opportunities',
    'view_opportunity' => 'View Opportunity',
    'create_follow_up' => 'Create Follow-up',
  ),
  'sections' => 
  array (
    'details' => 'Details',
    'client_details' => 'Client Details',
    'opportunities' => 'Opportunities',
    'follow_ups' => 'Follow-ups',
    'relations' => 'Relations',
    'follow_up_content' => 'Follow-up Content',
  ),
  'table_groups' => 
  array (
    'client' => 'Client',
    'company' => 'Company',
    'contact' => 'Contact',
    'assignment' => 'Assignment',
    'amounts' => 'Amounts',
    'commercial' => 'Commercial Terms',
    'scheduling' => 'Scheduling',
    'notes' => 'Notes',
    'dates' => 'Dates',
  ),
  'tabs' => 
  array (
    'all' => 'All',
    'upcoming' => 'Upcoming',
    'overdue' => 'Overdue',
    'completed' => 'Completed',
    'mine' => 'My Follow-ups',
  ),
  'filters' => 
  array (
    'my_follow_ups' => 'My Follow-ups',
    'upcoming_week' => 'This Week',
  ),
  'resources' => 
  array (
    'opportunity' => 
    array (
      'navigation' => 'Opportunities',
      'model' => 'Opportunity',
      'plural' => 'Opportunities',
    ),
    'campaign' => 
    array (
      'navigation' => 'Campaigns',
      'model' => 'Campaign',
      'plural' => 'Campaigns',
    ),
    'opportunity_stage' => 
    array (
      'navigation' => 'Opportunity Stages',
      'model' => 'Stage',
      'plural' => 'Opportunity Stages',
    ),
    'follow_up_status' => 
    array (
      'navigation' => 'Follow-up Statuses',
      'model' => 'Follow-up Status',
      'plural' => 'Follow-up Statuses',
    ),
    'follow_up_type' => 
    array (
      'navigation' => 'Follow-up Types',
      'model' => 'Follow-up Type',
      'plural' => 'Follow-up Types',
    ),
    'follow_up' => 
    array (
      'navigation' => 'Follow-ups',
      'model' => 'Follow-up',
      'plural' => 'Follow-ups',
    ),
    'lead_clients' => 
    array (
      'navigation' => 'Lead Clients',
    ),
  ),
  'fields' => 
  array (
    'basic_info' => 'Basic info',
    'name' => 'Name',
    'company_name' => 'Company',
    'gondc_name' => 'GONDC',
    'email' => 'Email',
    'phone' => 'Phone',
    'tax_number' => 'Tax number',
    'commercial_register' => 'Commercial register',
    'address' => 'Address',
    'stage' => 'Stage',
    'sales_rep' => 'Sales rep',
    'lead_source' => 'Lead source',
    'first_followed_by' => 'First followed by',
    'is_provisional' => 'Provisional',
    'client' => 'Client',
    'campaign' => 'Campaign',
    'title' => 'Title',
    'amount' => 'Amount',
    'agreed_amount' => 'Agreed Amount',
    'description' => 'Description',
    'is_closed' => 'Closed',
    'closed_at' => 'Closed At',
    'assigned_to' => 'Assigned To',
    'first_assigned_to' => 'First Assigned To',
    'created_by' => 'Created By',
    'created_at' => 'Created At',
    'updated_at' => 'Updated At',
    'meta' => 'Meta',
    'note' => 'Note',
    'latest_note' => 'Latest Note',
    'is_private' => 'Private',
    'private' => 'Private',
    'notes' => 'Notes',
    'from_stage' => 'From Stage',
    'to_stage' => 'To Stage',
    'changed_by' => 'Changed By',
    'from_user' => 'From User',
    'to_user' => 'To User',
    'follow_up_type' => 'Follow-up Type',
    'follow_up_status' => 'Follow-up Status',
    'target_stage' => 'Target Stage',
    'next_scheduled_at' => 'Next Follow-up Date',
    'next_assigned_to' => 'Next Follow-up Assignee',
    'next_follow_up_type' => 'Next Follow-up Type',
    'parent_follow_up' => 'Rescheduled From',
    'scheduled_at' => 'Scheduled At',
    'completed_at' => 'Completed At',
    'scheduling_state' => 'Status',
    'offer_text' => 'Offer Text',
    'customer_reply' => 'Customer Reply',
    'internal_notes' => 'Internal Notes',
    'start_date' => 'Start Date',
    'end_date' => 'End Date',
    'status' => 'Status',
    'budget' => 'Budget',
    'is_final' => 'Final Stage',
    'sort_order' => 'Sort Order',
    'action' => 'Action',
    'open_opportunities_count' => 'Open Opportunities',
    'won_opportunities_count' => 'Won Opportunities',
    'won_agreed_amount_total' => 'Won Agreed Amount',
    'latest_opportunity' => 'Latest Opportunity',
    'latest_opportunity_stage' => 'Latest Opportunity Stage',
    'last_completed_follow_up' => 'Last Completed Follow-up',
    'next_scheduled_follow_up' => 'Next Scheduled Follow-up',
    'opportunity' => 'Opportunity',
    'child_follow_ups' => 'Derived Follow-ups',
    'branch' => 'Branch',
    'source' => 'Source',
  ),
  'hints' => 
  array (
    'next_scheduled_at' => 'The current follow-up will be closed and a new follow-up created on this date.',
    'next_assigned_to' => 'Who will handle the next follow-up (defaults to you).',
  ),
  'notes' => 
  array (
    'plural' => 'Notes',
    'add' => 'Add Note',
    'view_all' => 'View All Notes',
    'modal_heading' => 'Notes',
    'created' => 'Note created',
    'empty' => 'No notes yet.',
    'private' => 'Private',
  ),
  'stage_logs' => 
  array (
    'plural' => 'Stage History',
  ),
  'assignment_logs' => 
  array (
    'plural' => 'Assignment History',
  ),
  'follow_ups' => 
  array (
    'plural' => 'Follow-ups',
  ),
  'client_stages' => 
  array (
    'lead' => 'Lead',
    'customer' => 'Customer',
    'advanced' => 'Advanced',
    'vip' => 'VIP',
  ),
  'stage_actions' => 
  array (
    'none' => 'None',
    'open' => 'Open',
    'success_close' => 'Won - Close',
    'failed_close' => 'Lost - Close',
    'reopen' => 'Reopen',
  ),
  'follow_up_actions' => 
  array (
    'none' => 'None',
    'success_close' => 'Won - Close',
    'failed_close' => 'Lost - Close',
    'change_stage' => 'Change Stage',
    'schedule_next' => 'Schedule Next',
  ),
  'scheduling' => 
  array (
    'scheduled' => 'Scheduled',
    'overdue' => 'Overdue',
    'completed' => 'Completed',
  ),
  'campaign_status_options' => 
  array (
    'draft' => 'Draft',
    'active' => 'Active',
    'paused' => 'Paused',
    'completed' => 'Completed',
  ),
  'callouts' => 
  array (
    'won_title' => 'Opportunity Won',
    'lost_title' => 'Opportunity Lost',
    'reopened_title' => 'Opportunity Reopened',
    'closed_title' => 'Opportunity Closed',
    'open_title' => 'Open Opportunity',
    'closed_at' => 'Closed at :date',
  ),
  'messages' => 
  array (
    'invalid_stage' => 'Invalid stage selected.',
    'target_stage_required' => 'Target stage is required for this status.',
    'next_scheduled_at_required' => 'Next scheduled date is required.',
    'cannot_save_client' => 'Cannot save client',
    'client_updated' => 'Client updated successfully',
    'opportunity_created' => 'Opportunity created successfully',
    'client_number' => 'Client #:id',
    'name_ar_taken' => 'Arabic name is already used by client (:name)',
    'name_en_taken' => 'English name is already used by client (:name)',
    'phone_taken' => 'Phone number is already used by client (:name)',
    'email_taken' => 'Email is already used by client (:name)',
  ),
  'summaries' => 
  array (
    'total_amount' => 'Total',
  ),
  'widgets' => 
  array (
    'open_opportunities' => 'Open Opportunities',
    'won_opportunities' => 'Won Opportunities',
    'lost_opportunities' => 'Lost Opportunities',
    'upcoming_follow_ups' => 'Upcoming Follow-ups',
    'overdue_follow_ups' => 'Overdue Follow-ups',
    'conversion_rate' => 'Conversion Rate',
    'follow_ups_pending' => 'Pending Follow-ups',
    'follow_ups_pending_desc' => 'Not yet completed',
    'follow_ups_upcoming' => 'Upcoming (7 days)',
    'follow_ups_upcoming_desc' => 'Scheduled this week',
    'follow_ups_completed_today' => 'Completed Today',
    'my_pending_follow_ups' => 'My Pending',
    'rescheduled_follow_ups' => 'Rescheduled',
    'rescheduled_follow_ups_desc' => 'Linked to a parent follow-up',
    'no_data' => 'No data',
    'commissions_heading' => 'Commissions Overview',
    'commission_pending' => 'Pending Commissions',
    'commission_approved' => 'Approved Commissions',
    'commission_paid' => 'Paid Commissions',
    'commission_partially_paid' => 'Partially paid: :count',
    'commission_effective' => 'Effective Entitlement',
    'commission_net_paid' => 'Net Paid',
    'commission_remaining' => 'Remaining',
    'chart_opportunities_by_stage' => 'Opportunities by Stage',
    'chart_opportunities_trend' => 'Opportunities Trend (6 months)',
    'trend_created' => 'Created',
    'trend_won' => 'Won',
    'chart_commissions_by_status' => 'Commissions by Status',
    'chart_clients_by_source' => 'Clients by Source',
  ),
  'enums' => 
  array (
    'commission_status' => 
    array (
      'draft' => 'Draft',
      'pending' => 'Pending approval',
      'approved' => 'Approved',
      'partially_paid' => 'Partially paid',
      'paid' => 'Paid',
      'rejected' => 'Rejected',
      'cancelled' => 'Cancelled',
    ),
    'commission_type' => 
    array (
      'sales' => 'Sales',
      'referral' => 'Referral',
      'bonus' => 'Bonus',
      'override' => 'Override',
      'adjustment' => 'Adjustment',
    ),
    'commission_adjustment_direction' => 
    array (
      'increase' => 'Increase',
      'decrease' => 'Decrease',
    ),
    'commission_adjustment_status' => 
    array (
      'pending' => 'Pending',
      'approved' => 'Approved',
      'rejected' => 'Rejected',
      'cancelled' => 'Cancelled',
    ),
    'commission_payment_cycle_status' => 
    array (
      'draft' => 'Draft',
      'pending_approval' => 'Pending approval',
      'approved' => 'Approved',
      'partially_paid' => 'Partially paid',
      'paid' => 'Paid',
      'cancelled' => 'Cancelled',
    ),
    'commission_payment_entry_type' => 
    array (
      'payment' => 'Payment',
      'reversal' => 'Reversal',
    ),
  ),
  'commissions' => 
  array (
    'navigation' => 'Commissions',
    'model' => 'Commission',
    'plural' => 'Commissions',
    'relation_title' => 'Commissions',
    'automatic' => 
    array (
      'notes' => 'This commission was automatically created when the opportunity was closed as won.',
    ),
    'sections' => 
    array (
      'details' => 'Commission details',
      'financial_summary' => 'Financial summary',
      'audit_log' => 'Audit log',
    ),
    'fields' => 
    array (
      'employee' => 'Employee',
      'commission_type' => 'Commission type',
      'base_amount' => 'Base amount',
      'commission_percentage' => 'Commission %',
      'commission_amount' => 'Commission amount',
      'paid_amount' => 'Paid',
      'remaining_amount' => 'Remaining',
      'due_at' => 'Due date',
      'approved_at' => 'Approved at',
      'approved_by' => 'Approved by',
      'rejection_reason' => 'Rejection reason',
      'cancellation_reason' => 'Cancellation reason',
      'adjustment_delta' => 'Adjustment amount (+/-)',
      'recalculate_preview' => 'Recalculate preview',
      'audit_action' => 'Action',
      'amount_before' => 'Amount before',
      'amount_after' => 'Amount after',
    ),
    'actions' => 
    array (
      'submit' => 'Submit for approval',
      'approve' => 'Approve',
      'reject' => 'Reject',
      'cancel' => 'Cancel',
      'recalculate' => 'Recalculate',
      'create_adjustment' => 'Create adjustment',
    ),
    'audit_actions' => 
    array (
      'created' => 'Created',
      'updated' => 'Updated',
      'submitted' => 'Submitted',
      'approved' => 'Approved',
      'rejected' => 'Rejected',
      'cancelled' => 'Cancelled',
      'recalculated' => 'Recalculated',
      'adjustment_created' => 'Adjustment created',
      'adjustment_approved' => 'Adjustment approved',
      'adjustment_rejected' => 'Adjustment rejected',
      'adjustment_cancelled' => 'Adjustment cancelled',
    ),
    'adjustments' => 
    array (
      'relation_title' => 'Adjustments',
      'empty' => 'No adjustments yet.',
      'fields' => 
      array (
        'direction' => 'Direction',
        'amount' => 'Amount',
        'reason' => 'Reason',
        'balance_before' => 'Balance before',
        'balance_after' => 'Balance after',
        'approved_by' => 'Approved by',
        'rejected_by' => 'Rejected by',
        'approved_at' => 'Approved at',
        'rejected_at' => 'Rejected at',
        'rejection_reason' => 'Rejection reason',
        'original_amount' => 'Original commission amount',
        'approved_increase_total' => 'Approved increase adjustments',
        'approved_decrease_total' => 'Approved decrease adjustments',
        'effective_amount' => 'Effective commission amount',
        'net_paid_amount' => 'Net paid amount',
        'remaining_amount' => 'Remaining amount',
        'decrease_preview' => 'Decrease preview',
        'resulting_effective_amount' => 'Resulting effective amount',
        'reversal_adjustment' => 'Reversal adjustment',
      ),
      'actions' => 
      array (
        'create' => 'Create adjustment',
        'approve' => 'Approve adjustment',
        'reject' => 'Reject adjustment',
        'cancel' => 'Cancel adjustment',
      ),
      'notifications' => 
      array (
        'created' => 'Adjustment created and pending approval',
        'approved' => 'Adjustment approved',
        'rejected' => 'Adjustment rejected',
        'cancelled' => 'Adjustment cancelled',
      ),
      'confirmations' => 
      array (
        'cancel' => 'This pending adjustment will be cancelled. It will not affect the commission balance.',
        'approve' => 'Review the effective amounts before approving this adjustment.',
      ),
      'messages' => 
      array (
        'decrease_preview' => 'Current effective: :current | Decrease: :amount | Projected effective: :projected | Net paid: :net_paid',
      ),
      'hints' => 
      array (
        'amount_positive' => 'Enter a positive amount. Direction determines increase or decrease.',
      ),
      'errors' => 
      array (
        'already_processed' => 'This adjustment has already been processed.',
      ),
    ),
    'notifications' => 
    array (
      'submitted' => 'Commission submitted for approval',
      'approved' => 'Commission approved',
      'rejected' => 'Commission rejected',
      'cancelled' => 'Commission cancelled',
      'recalculated' => 'Commission recalculated',
      'adjustment_created' => 'Adjustment recorded',
    ),
    'confirmations' => 
    array (
      'recalculate' => 'Base amount will be refreshed from the opportunity and commission amount recalculated.',
    ),
    'messages' => 
    array (
      'recalculate_preview' => 'Base: :old_base → :new_base | Amount: :old_amount → :new_amount | %: :percentage%',
    ),
    'hints' => 
    array (
      'adjustment_delta' => 'Enter a positive or negative value to adjust the approved commission amount.',
    ),
    'errors' => 
    array (
      'adjustment_disabled' => 'Commission adjustments are disabled until an independent adjustment ledger is implemented.',
      'adjustment_not_pending' => 'This adjustment is no longer pending.',
      'payment_already_reversed' => 'This payment has already been reversed.',
    ),
    'validation' => 
    array (
      'base_amount_zero' => 'Base amount is zero — cannot derive percentage from amount.',
      'base_amount_negative' => 'Base amount cannot be negative.',
      'negative_value' => 'Value cannot be negative.',
      'cannot_derive_percentage_from_zero_base' => 'Cannot derive percentage when base amount is zero.',
      'percentage_over_limit' => 'Percentage exceeds the allowed maximum (:max%).',
      'only_draft_can_be_submitted' => 'Only draft commissions can be submitted.',
      'rejection_reason_required' => 'Rejection reason is required.',
      'cancellation_reason_required' => 'Cancellation reason is required.',
      'cannot_cancel_with_payments' => 'Cannot cancel a commission with recorded payments.',
      'adjustment_notes_required' => 'Adjustment notes are required.',
      'adjustment_reason_required' => 'Adjustment reason is required.',
      'adjustment_rejection_reason_required' => 'Rejection reason is required.',
      'adjustment_amount_must_be_positive' => 'Adjustment amount must be greater than zero.',
      'adjustment_decrease_below_net_paid' => 'Decrease would reduce entitlement below net paid amount.',
      'adjustment_negative_effective' => 'Adjustment would result in a negative commission amount.',
      'payment_amount_must_be_positive' => 'Payment amount must be greater than zero.',
      'payment_amount_exceeds_remaining' => 'Payment amount exceeds the remaining commission balance.',
      'cycle_not_payable' => 'This payment cycle is not ready for payment execution.',
      'cycle_has_no_allocations' => 'The payment cycle has no commission allocations.',
      'cycle_allocations_required' => 'At least one commission allocation is required.',
      'only_draft_cycle_can_be_edited' => 'Only draft payment cycles can be edited.',
      'only_draft_cycle_can_be_submitted' => 'Only draft payment cycles can be submitted for approval.',
      'only_payments_can_be_reversed' => 'Only payment ledger entries can be reversed.',
      'reversal_reason_required' => 'Reversal reason is required.',
      'commission_not_payable' => 'Commission #:id is not payable in its current state.',
      'allocation_user_mismatch' => 'Allocation employee must match the commission owner.',
      'duplicate_cycle_allocation' => 'Each commission can appear only once in a payment cycle.',
    ),
    'empty' => 'No commissions yet.',
  ),
  'payment_cycles' => 
  array (
    'navigation' => 'Payment cycles',
    'model' => 'Payment cycle',
    'plural' => 'Payment cycles',
    'sections' => 
    array (
      'details' => 'Cycle details',
      'financial_summary' => 'Financial summary',
    ),
    'fields' => 
    array (
      'cycle_number' => 'Cycle number',
      'period_from' => 'Period from',
      'period_to' => 'Period to',
      'payment_date' => 'Payment date',
      'payment_method' => 'Payment method',
      'reference_number' => 'Reference number',
      'approved_by' => 'Approved by',
      'paid_by' => 'Paid by',
      'planned_total' => 'Planned total',
      'total_paid' => 'Total paid',
      'total_reversed' => 'Total reversed',
      'net_paid' => 'Net paid',
      'employee_scope' => 'Employee scope',
      'employees' => 'Employees',
      'commissions' => 'Commissions',
      'payment_mode' => 'Payment mode',
      'planned_payment_amount' => 'Planned payment amount',
      'amount' => 'Amount',
      'entry_type' => 'Entry type',
      'executed_at' => 'Executed at',
      'executed_by' => 'Executed by',
      'cancellation_reason' => 'Cancellation reason',
      'reversal_reason' => 'Reversal reason',
    ),
    'actions' => 
    array (
      'create' => 'Create payment cycle',
      'submit' => 'Submit for approval',
      'approve' => 'Approve',
      'execute_payment' => 'Execute Payment',
      'cancel' => 'Cancel cycle',
      'reverse_payment' => 'Reverse payment',
    ),
    'modes' => 
    array (
      'single_employee' => 'Single employee',
      'multiple_employees' => 'Multiple employees',
      'all_employees' => 'All employees',
      'full_payment' => 'Full payment',
      'partial_payment' => 'Partial payment',
    ),
    'wizard' => 
    array (
      'title' => 'Create payment cycle',
      'previous' => 'Previous',
      'next' => 'Next',
      'submit' => 'Create cycle',
      'preview_summary' => 'Summary',
      'submit_for_approval' => 'Submit for approval after creation',
      'submit_for_approval_help' => 'Leave unchecked to save as draft.',
      'no_payable_commissions' => 'No payable commissions found for the selected period and scope.',
      'descriptions' => 
      array (
        'period_scope' => 'Choose the entitlement period, the branch, and which employees to include.',
        'payment_amounts' => 'Set how much of each remaining balance to plan for payment.',
        'payment_details' => 'Payment method and reference. Execution happens later, after approval.',
        'review' => 'Review the plan before creating the draft cycle.',
      ),
      'callouts' => 
      array (
        'allocation_plan' => 'This step is a payment plan only. No payment is recorded yet.',
        'allocation_vs_payment' => 'This amount is a payment allocation only. No payment is recorded until the cycle is approved and executed.',
        'execution_later' => 'Actual execution happens later, after the cycle is approved.',
      ),
      'commissions_found' => ':count payable commission(s) found.',
      'commission_option' => ':employee — :opportunity — remaining :remaining (due :due)',
      'steps' => 
      array (
        'period_scope' => 'Period & scope',
        'select_commissions' => 'Select commissions',
        'payment_amounts' => 'Payment amounts',
        'payment_details' => 'Payment details',
        'review' => 'Review & submit',
      ),
      'preview' => 
      array (
        'period' => 'Period: :from → :to',
        'allocations_count' => 'Allocations: :count',
        'planned_total' => 'Planned total: :amount',
        'payment_date' => 'Payment date: :date',
        'payment_method' => 'Payment method: :method',
      ),
    ),
    'payments' => 
    array (
      'relation_title' => 'Payments',
      'empty' => 'No payments recorded for this cycle yet.',
    ),
    'notifications' => 
    array (
      'created' => 'Payment cycle created',
      'submitted' => 'Payment cycle submitted for approval',
      'approved' => 'Payment cycle approved',
      'cancelled' => 'Payment cycle cancelled',
      'payments_executed' => 'Payments executed successfully',
      'payment_reversed' => 'Payment reversed',
    ),
    'confirmations' => 
    array (
      'submit' => 'This draft cycle will be submitted for approval.',
      'approve' => 'Approve this payment cycle?',
      'execute_payment' => 'Execute all planned payments for this cycle? This creates immutable ledger entries.',
    ),
    'messages' => 
    array (
      'payments_executed_count' => ':count payment(s) recorded.',
    ),
    'validation' => 
    array (
      'partial_not_allowed' => 'Partial payments are not allowed for your role.',
      'full_amount_mismatch' => 'Full payment amount must equal the remaining balance.',
    ),
    'empty' => 'No payment cycles yet.',
  ),
  'reports' => 
  array (
    'common' => 
    array (
      'not_specified' => 'Not specified',
      'not_applicable' => 'N/A',
      'yes' => 'Yes',
      'no' => 'No',
      'row_count' => 'Rows',
    ),
    'actions' => 
    array (
      'export' => 'Export',
      'print' => 'Print report',
    ),
    'print' => 
    array (
      'generated_at' => 'Generated at',
      'generated_by' => 'Generated by',
      'applied_filters' => 'Applied filters',
      'date_basis' => 'Date basis: :basis',
      'date_range' => 'Period: :from — :to',
      'branch' => 'Branch: :value',
      'employee' => 'Employee: :value',
      'source' => 'Source: :value',
      'client_stage' => 'Client stage: :value',
      'campaign' => 'Campaign: :value',
      'campaign_status' => 'Campaign status: :value',
      'opportunity_stage' => 'Opportunity stage: :value',
      'opportunity_status' => 'Opportunity status: :value',
      'client' => 'Client: :value',
      'opportunity' => 'Opportunity: :value',
      'amount_range' => 'Amount: :from — :to',
      'has_opportunities' => 'Has opportunities: :value',
      'has_won_opportunity' => 'Has won opportunity: :value',
      'follow_up_type' => 'Follow-up type: :value',
      'follow_up_status' => 'Follow-up status: :value',
      'follow_up_scheduling' => 'Scheduling: :value',
      'row_limit_reached' => 'Showing first :max rows',
    ),
    'filters' => 
    array (
      'date_range' => 'Date range',
      'date_basis' => 'Date basis',
      'basis_created_at' => 'Created at',
      'basis_updated_at' => 'Updated at',
      'basis_closed_at' => 'Closed at',
      'basis_scheduled_at' => 'Scheduled at',
      'basis_completed_at' => 'Completed at',
      'basis_start_date' => 'Campaign start date',
      'basis_approved_at' => 'Approved at',
      'basis_client_created_at' => 'Client created at',
      'basis_opportunity_created_at' => 'Opportunity created at',
      'from_date' => 'From',
      'to_date' => 'To',
      'amount_range' => 'Amount range',
      'amount_from' => 'Amount from',
      'amount_to' => 'Amount to',
      'has_opportunities' => 'Has opportunities',
      'has_won_opportunity' => 'Has won opportunity',
      'opportunity_status' => 'Opportunity status',
      'status_open' => 'Open',
      'status_won' => 'Won',
      'status_lost' => 'Lost',
    ),
    'customer' => 
    array (
      'navigation' => 'Customer reports',
      'title' => 'Customer reports',
      'stats' => 
      array (
        'total_clients' => 'Total clients',
        'new_clients' => 'New clients',
        'with_opportunities' => 'With opportunities',
        'without_opportunities' => 'Without opportunities',
        'with_won_opportunities' => 'With won opportunities',
        'conversion_rate' => 'Conversion rate',
        'average_opportunities' => 'Average opportunities per client',
      ),
      'columns' => 
      array (
        'opportunities_count' => 'Opportunities',
        'won_opportunities_count' => 'Won opportunities',
        'agreed_amount_total' => 'Total agreed amount',
        'last_follow_up' => 'Last follow-up',
      ),
      'charts' => 
      array (
        'by_stage' => 'Clients by stage',
      ),
      'export' => 
      array (
        'name' => 'Customer report export',
        'completed' => ':count row(s) exported.',
      ),
    ),
    'source' => 
    array (
      'navigation' => 'Source reports',
      'title' => 'Source reports',
      'stats' => 
      array (
        'clients_total' => 'Total clients',
        'opportunities_total' => 'Total opportunities',
        'open_total' => 'Open',
        'won_total' => 'Won',
        'lost_total' => 'Lost',
        'amount_total' => 'Total amount',
        'agreed_amount_total' => 'Total agreed amount',
        'conversion_rate' => 'Conversion rate',
      ),
      'columns' => 
      array (
        'source' => 'Source',
        'clients_count' => 'Clients',
        'opportunities_count' => 'Opportunities',
        'open_count' => 'Open',
        'won_count' => 'Won',
        'lost_count' => 'Lost',
        'conversion_rate' => 'Conversion rate',
        'average_amount' => 'Average amount',
      ),
      'export' => 
      array (
        'name' => 'Source report export',
        'completed' => ':count row(s) exported.',
      ),
    ),
    'opportunity' => 
    array (
      'navigation' => 'Opportunity reports',
      'title' => 'Opportunity reports',
      'stats' => 
      array (
        'total' => 'Total opportunities',
        'open' => 'Open',
        'won' => 'Won',
        'lost' => 'Lost',
        'amount_total' => 'Total amount',
        'agreed_amount_total' => 'Total agreed amount',
        'close_rate' => 'Close rate',
        'success_rate' => 'Success rate',
        'average_close_days' => 'Average close duration (days)',
      ),
      'columns' => 
      array (
        'close_duration' => 'Close duration (days)',
        'last_follow_up' => 'Last follow-up',
        'follow_ups_count' => 'Follow-ups',
      ),
      'charts' => 
      array (
        'by_stage' => 'Opportunities by stage',
      ),
      'export' => 
      array (
        'name' => 'Opportunity report export',
        'completed' => ':count row(s) exported.',
      ),
    ),
    'followup' => 
    array (
      'navigation' => 'Follow-up reports',
      'title' => 'Follow-up reports',
      'stats' => 
      array (
        'total' => 'Total follow-ups',
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'overdue' => 'Overdue',
        'completed_on_time' => 'Completed on time',
        'average_per_opportunity' => 'Average follow-ups per opportunity',
        'opportunities_without_follow_up' => 'Opportunities without follow-up',
        'clients_without_follow_up' => 'Clients without follow-up',
      ),
      'columns' => 
      array (
        'scheduling_state' => 'Scheduling state',
      ),
      'filters' => 
      array (
        'scheduling' => 'Scheduling',
      ),
      'scheduling' => 
      array (
        'scheduled' => 'Scheduled',
        'overdue' => 'Overdue',
        'completed' => 'Completed',
      ),
      'charts' => 
      array (
        'by_employee' => 'Follow-ups by employee',
        'by_type' => 'Follow-ups by type',
        'by_status' => 'Follow-ups by status',
      ),
      'export' => 
      array (
        'name' => 'Follow-up report export',
        'completed' => ':count row(s) exported.',
      ),
    ),
    'campaign' => 
    array (
      'navigation' => 'Campaign reports',
      'title' => 'Campaign reports',
      'stats' => 
      array (
        'campaigns_count' => 'Campaigns',
        'opportunities_total' => 'Total opportunities',
        'won_total' => 'Won',
        'lost_total' => 'Lost',
        'amount_total' => 'Total amount',
        'agreed_amount_total' => 'Total agreed amount',
        'conversion_rate' => 'Conversion rate',
        'expected_roi' => 'Expected ROI',
      ),
      'columns' => 
      array (
        'campaign' => 'Campaign',
        'budget' => 'Budget',
        'opportunities_count' => 'Opportunities',
        'won_count' => 'Won',
        'lost_count' => 'Lost',
        'conversion_rate' => 'Conversion rate',
        'cost_per_opportunity' => 'Cost per opportunity',
        'cost_per_won' => 'Cost per won opportunity',
        'expected_roi' => 'Expected ROI',
      ),
      'charts' => 
      array (
        'by_status' => 'Campaigns by status',
      ),
      'export' => 
      array (
        'name' => 'Campaign report export',
        'completed' => ':count row(s) exported.',
      ),
    ),
    'employee' => 
    array (
      'navigation' => 'Employee performance',
      'title' => 'Employee performance reports',
      'stats' => 
      array (
        'employees_count' => 'Employees',
        'clients_total' => 'Clients',
        'opportunities_total' => 'Opportunities',
        'conversion_rate' => 'Conversion rate',
        'average_close_days' => 'Average close duration (days)',
        'completed_follow_ups' => 'Completed follow-ups',
        'overdue_follow_ups' => 'Overdue follow-ups',
        'effective_commissions_total' => 'Effective commissions',
        'net_paid_total' => 'Net paid',
        'remaining_total' => 'Remaining',
      ),
      'columns' => 
      array (
        'employee' => 'Employee',
        'clients' => 'Clients',
        'opportunities' => 'Opportunities',
        'open' => 'Open',
        'won' => 'Won',
        'lost' => 'Lost',
        'conversion_rate' => 'Conversion rate',
        'average_close_days' => 'Average close duration (days)',
        'completed_follow_ups' => 'Completed follow-ups',
        'overdue_follow_ups' => 'Overdue follow-ups',
        'effective_commissions' => 'Effective commissions',
        'net_paid' => 'Net paid',
        'remaining' => 'Remaining',
      ),
      'rankings' => 
      array (
        'title' => 'Top employees',
        'by_won' => 'By won opportunities',
        'by_agreed_amount' => 'By agreed amount',
        'by_conversion' => 'By conversion rate',
        'by_follow_up_completion' => 'By follow-up completion',
      ),
      'export' => 
      array (
        'name' => 'Employee performance export',
        'completed' => ':count row(s) exported.',
      ),
    ),
    'errors' => 
    array (
      'unauthorized' => 'You are not allowed to view this report.',
    ),
  ),
  'own_commissions' => 
  array (
    'navigation' => 'My commissions',
    'detail_title' => 'Commission — :opportunity',
    'sections' => 
    array (
      'details' => 'Commission details',
      'adjustments' => 'Adjustments',
      'payments' => 'Payments',
      'audit' => 'Status history',
    ),
    'fields' => 
    array (
      'opportunity_number' => 'Opportunity #',
      'original_amount' => 'Original amount',
      'increase_adjustments' => 'Approved increases',
      'decrease_adjustments' => 'Approved decreases',
      'effective_amount' => 'Effective entitlement',
      'net_paid' => 'Net paid',
      'remaining' => 'Remaining',
      'approved_at' => 'Approved at',
      'due_at' => 'Due date',
      'cycle_number' => 'Cycle number',
      'remaining_before' => 'Remaining before',
      'remaining_after' => 'Remaining after',
    ),
    'totals' => 
    array (
      'original' => 'Total original commissions',
      'effective' => 'Total effective entitlement',
      'net_paid' => 'Total net paid',
      'remaining' => 'Total remaining',
      'increase_adjustments' => 'Total approved increases',
      'decrease_adjustments' => 'Total approved decreases',
      'pending_count' => 'Pending review',
      'opportunity_count' => 'Opportunities with commissions',
    ),
    'filters' => 
    array (
      'date_range' => 'Date range',
      'date_basis' => 'Date basis',
      'basis_created_at' => 'Created at',
      'basis_approved_at' => 'Approved at',
      'basis_due_at' => 'Due date',
      'from_date' => 'From',
      'to_date' => 'To',
      'payment_settlement' => 'Payment status',
      'fully_paid' => 'Fully paid',
      'partially_paid' => 'Partially paid',
      'unpaid' => 'Unpaid',
      'include_history' => 'Include rejected & cancelled',
    ),
    'actions' => 
    array (
      'export' => 'Export my commissions',
      'back_to_list' => 'Back to my commissions',
    ),
    'export' => 
    array (
      'name' => 'My commissions export',
      'completed' => ':count commission(s) exported.',
    ),
    'empty' => 
    array (
      'list' => 'You have no commissions to display.',
      'adjustments' => 'No adjustments on this commission.',
      'payments' => 'No payments recorded yet.',
      'audit' => 'No status history yet.',
    ),
    'errors' => 
    array (
      'unauthorized' => 'You are not allowed to view this commission.',
    ),
  ),
  'timeline' => 
  array (
    'title' => 'Activity Timeline',
    'stage_change' => 'Stage changed',
    'assignment' => 'Assignment changed',
    'note' => 'Note added',
    'follow_up_created' => 'Follow-up scheduled',
    'follow_up_completed' => 'Follow-up completed',
  ),
);
