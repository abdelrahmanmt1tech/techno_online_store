@extends('whatsapp.onboarding.layout')

@section('title', __('dashboard.whatsapp_onboarding_callback_title'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.whatsapp_onboarding_central_badge') }}</span>
        <h1>{{ __('dashboard.whatsapp_onboarding_callback_title') }}</h1>
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
                <dt>{{ __('dashboard.whatsapp_onboarding_status_value') }}</dt>
                <dd>{{ $session?->status ?: __('dashboard.whatsapp_onboarding_result_not_started') }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_onboarding_received_query_keys') }}</dt>
                <dd>
                    @forelse ($receivedKeys as $key)
                        <code>{{ $key }}</code>@if (! $loop->last), @endif
                    @empty
                        —
                    @endforelse
                </dd>
            </div>
        </dl>

        <div class="actions">
            <a class="button" href="{{ route('whatsapp.onboarding.status', ['state' => request('state')]) }}">
                {{ __('dashboard.whatsapp_onboarding_status_title') }}
            </a>
            <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.whatsapp_onboarding_return_tenant') }}</a>
        </div>
    </div>
@endsection
