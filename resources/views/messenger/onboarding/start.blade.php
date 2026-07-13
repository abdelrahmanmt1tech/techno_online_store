@extends('messenger.onboarding.layout')

@section('title', __('dashboard.messenger_connect_via_facebook'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.messenger_onboarding_central_badge') }}</span>
        <h1>{{ __('dashboard.messenger_connect_via_facebook') }}</h1>
        <p class="muted">{{ __('dashboard.messenger_onboarding_start_intro') }}</p>

        @if (! $configured)
            <div class="alert">
                {{ __('dashboard.messenger_onboarding_config_required_body') }}
            </div>
            <dl>
                <div>
                    <dt>{{ __('dashboard.messenger_onboarding_required_env') }}</dt>
                    <dd>
                        <code>META_APP_ID</code>,
                        <code>META_APP_SECRET</code>,
                        <code>MESSENGER_FACEBOOK_LOGIN_CONFIG_ID</code>,
                        <code>MESSENGER_OAUTH_REDIRECT_URI</code>
                    </dd>
                </div>
            </dl>
            <div class="actions">
                <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.messenger_onboarding_return') }}</a>
            </div>
        @else
            <ul class="muted" style="padding-inline-start: 1.25rem;">
                <li>{{ __('dashboard.messenger_onboarding_start_point_1') }}</li>
                <li>{{ __('dashboard.messenger_onboarding_start_point_2') }}</li>
                <li>{{ __('dashboard.messenger_onboarding_start_point_3') }}</li>
            </ul>
            <dl style="margin-top: 1.25rem;">
                <div>
                    <dt>{{ __('dashboard.messenger_onboarding_scopes') }}</dt>
                    <dd><code>{{ $scopes }}</code></dd>
                </div>
                <div>
                    <dt>Config ID</dt>
                    <dd><code>{{ $configId }}</code></dd>
                </div>
            </dl>
            <div class="actions">
                <a class="button" href="{{ $oauthUrl }}">{{ __('dashboard.messenger_onboarding_launch_cta') }}</a>
                <a class="button secondary" href="{{ $statusUrl }}">{{ __('dashboard.messenger_onboarding_status_link') }}</a>
                <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.messenger_onboarding_return') }}</a>
            </div>
        @endif
    </div>
@endsection
