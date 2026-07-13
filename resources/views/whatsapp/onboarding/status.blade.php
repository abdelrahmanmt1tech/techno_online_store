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
                <dt>{{ __('dashboard.whatsapp_onboarding_status_value') }}</dt>
                <dd>{{ $statusLabel }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_connection_method') }}</dt>
                <dd>{{ $state->connectionMethod->label() }}</dd>
            </div>
            @if ($session)
                <div>
                    <dt>{{ __('dashboard.whatsapp_waba_id') }}</dt>
                    <dd>{{ $session->waba_id ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('dashboard.whatsapp_phone_number_id') }}</dt>
                    <dd>{{ $session->phone_number_id ?: '—' }}</dd>
                </div>
                <div>
                    <dt>{{ __('dashboard.whatsapp_onboarding_has_token') }}</dt>
                    <dd>{{ filled($session->masked_access_token) ? __('dashboard.whatsapp_onboarding_token_stored') : '—' }}</dd>
                </div>
                @if (filled($session->last_error))
                    <div>
                        <dt>{{ __('dashboard.whatsapp_error_details') }}</dt>
                        <dd>{{ $session->last_error }}</dd>
                    </div>
                @endif
            @else
                <div>
                    <dt>{{ __('dashboard.whatsapp_onboarding_session') }}</dt>
                    <dd>{{ __('dashboard.whatsapp_onboarding_result_not_started') }}</dd>
                </div>
            @endif
        </dl>

        <div class="actions">
            <a class="button" href="{{ $state->returnUrl }}">{{ __('dashboard.whatsapp_onboarding_return_tenant') }}</a>
            @if (! $session || in_array($session->status, ['failed', 'cancelled', null], true))
                <a class="button secondary" href="{{ route('whatsapp.onboarding.start', ['state' => request('state')]) }}">
                    {{ __('dashboard.whatsapp_onboarding_launch_cta') }}
                </a>
            @endif
        </div>
    </div>
@endsection
