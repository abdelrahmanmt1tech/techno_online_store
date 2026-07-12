@extends('whatsapp.onboarding.layout')

@section('title', __('dashboard.whatsapp_onboarding_start_title'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.whatsapp_onboarding_central_badge') }}</span>
        <h1>{{ __('dashboard.whatsapp_onboarding_start_title') }}</h1>
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

        <div class="actions">
            <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.whatsapp_onboarding_return_tenant') }}</a>
            <a class="button secondary" href="{{ route('whatsapp.onboarding.status', ['state' => request('state')]) }}">{{ __('dashboard.whatsapp_onboarding_status_title') }}</a>
        </div>
    </div>
@endsection
