@extends('whatsapp.onboarding.layout')

@section('title', __('dashboard.whatsapp_onboarding_status_title'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.whatsapp_onboarding_central_badge') }}</span>
        <h1>{{ __('dashboard.whatsapp_onboarding_status_title') }}</h1>
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
                <dt>{{ __('dashboard.whatsapp_onboarding_state_expires_at') }}</dt>
                <dd>{{ \Illuminate\Support\Carbon::createFromTimestamp($state->expiresAt)->toDateTimeString() }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_onboarding_status_value') }}</dt>
                <dd>{{ __('dashboard.whatsapp_onboarding_status_skeleton') }}</dd>
            </div>
        </dl>

        <div class="actions">
            <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.whatsapp_onboarding_return_tenant') }}</a>
        </div>
    </div>
@endsection
