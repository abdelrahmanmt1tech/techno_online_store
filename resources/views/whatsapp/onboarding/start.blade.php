@extends('whatsapp.onboarding.layout')

@section('title', __('dashboard.whatsapp_onboarding_start_title'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.whatsapp_onboarding_central_badge') }}</span>
        <h1>{{ __('dashboard.whatsapp_connect_via_meta') }}</h1>
        <p class="muted">{{ $phaseNote }}</p>

        <dl>
            <div>
                <dt>{{ __('dashboard.whatsapp_tenant') }}</dt>
                <dd>{{ $state->tenantId }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_connection_method') }}</dt>
                <dd>{{ $state->connectionMethod->label() }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_embedded_signup_config_id') }}</dt>
                <dd>{{ filled($configId) ? $configId : '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_meta_app_id') }}</dt>
                <dd>{{ filled($metaAppId) ? $metaAppId : '—' }}</dd>
            </div>
        </dl>

        <div id="wa-onboarding-message" class="muted" style="margin-top: 1rem;" hidden></div>

        <div class="actions">
            @if ($canLaunch)
                <button type="button" id="wa-launch-embedded-signup" class="button">
                    {{ __('dashboard.whatsapp_onboarding_launch_cta') }}
                </button>
            @else
                <p class="muted">{{ __('dashboard.whatsapp_onboarding_missing_meta_config') }}</p>
            @endif
            <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.whatsapp_onboarding_cancel_return') }}</a>
            <a class="button secondary" href="{{ $statusUrl }}">{{ __('dashboard.whatsapp_onboarding_status_title') }}</a>
        </div>
    </div>

    @if ($canLaunch)
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
        <script>
            (function () {
                const metaAppId = @json($metaAppId);
                const configId = @json($configId);
                const graphVersion = @json($graphVersion);
                const stateToken = @json($stateToken);
                const completeUrl = @json($completeUrl);
                const statusUrl = @json($statusUrl);
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                let sessionInfo = {};
                let completing = false;

                const messageEl = document.getElementById('wa-onboarding-message');
                const launchBtn = document.getElementById('wa-launch-embedded-signup');

                function showMessage(text) {
                    if (!messageEl) return;
                    messageEl.hidden = false;
                    messageEl.textContent = text;
                }

                window.fbAsyncInit = function () {
                    FB.init({
                        appId: metaAppId,
                        autoLogAppEvents: true,
                        xfbml: true,
                        version: graphVersion
                    });
                };

                window.addEventListener('message', function (event) {
                    if (!String(event.origin || '').endsWith('facebook.com')) {
                        return;
                    }

                    try {
                        const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                        if (!data || data.type !== 'WA_EMBEDDED_SIGNUP') {
                            return;
                        }

                        sessionInfo = Object.assign({}, data.data || {}, {
                            event: data.event || null,
                            type: data.type
                        });

                        if (data.event === 'CANCEL') {
                            completeOnboarding('cancelled', null, sessionInfo, data.data?.error_message || null);
                        }
                    } catch (e) {
                        // Ignore non-JSON Facebook noise.
                    }
                });

                async function completeOnboarding(outcome, code, session, errorMessage) {
                    if (completing) {
                        return;
                    }
                    completing = true;
                    if (launchBtn) {
                        launchBtn.disabled = true;
                    }
                    showMessage(@json(__('dashboard.whatsapp_onboarding_completing')));

                    try {
                        const response = await fetch(completeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken || '',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                state: stateToken,
                                outcome: outcome,
                                code: code,
                                session: session || {},
                                error: errorMessage || null
                            })
                        });

                        const payload = await response.json();
                        if (payload.redirect) {
                            window.location.href = payload.redirect;
                            return;
                        }

                        showMessage(payload.message || @json(__('dashboard.whatsapp_onboarding_complete_failed')));
                        completing = false;
                        if (launchBtn) {
                            launchBtn.disabled = false;
                        }
                    } catch (e) {
                        showMessage(@json(__('dashboard.whatsapp_onboarding_complete_failed')));
                        completing = false;
                        if (launchBtn) {
                            launchBtn.disabled = false;
                        }
                    }
                }

                function fbLoginCallback(response) {
                    if (response && response.authResponse && response.authResponse.code) {
                        completeOnboarding('success', response.authResponse.code, sessionInfo, null);
                        return;
                    }

                    completeOnboarding('cancelled', null, sessionInfo, null);
                }

                function launchWhatsAppSignup() {
                    if (typeof FB === 'undefined') {
                        showMessage(@json(__('dashboard.whatsapp_onboarding_sdk_loading')));
                        return;
                    }

                    FB.login(fbLoginCallback, {
                        config_id: configId,
                        response_type: 'code',
                        override_default_response_type: true,
                        extras: {
                            setup: {}
                        }
                    });
                }

                if (launchBtn) {
                    launchBtn.addEventListener('click', launchWhatsAppSignup);
                }
            })();
        </script>
    @endif
@endsection
