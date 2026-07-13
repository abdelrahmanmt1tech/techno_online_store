@extends('whatsapp.onboarding.layout')

@section('title', __('dashboard.whatsapp_onboarding_status_title'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.whatsapp_onboarding_central_badge') }}</span>
        <h1>{{ __('dashboard.whatsapp_onboarding_status_title') }}</h1>
        <p class="muted">{{ $phaseNote }}</p>

        @if (session('error'))
            <p class="muted" style="color:#b45309;">{{ session('error') }}</p>
        @endif

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
                    <dt>{{ __('dashboard.whatsapp_display_phone') }}</dt>
                    <dd>{{ $session->display_phone_number ?: '—' }}</dd>
                </div>
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
                @if ($isSuccess)
                    <div>
                        <dt>{{ __('dashboard.whatsapp_onboarding_next_step') }}</dt>
                        <dd>
                            @if ($isCoexistence)
                                {{ __('dashboard.whatsapp_onboarding_next_step_coexistence') }}
                            @else
                                {{ __('dashboard.whatsapp_onboarding_next_step_test_messages') }}
                            @endif
                        </dd>
                    </div>
                @endif
                @if ($isCoexistence && in_array($session->status, ['in_progress', 'awaiting_phone_selection'], true))
                    <div>
                        <dt>{{ __('dashboard.whatsapp_onboarding_next_step') }}</dt>
                        <dd>{{ __('dashboard.whatsapp_onboarding_coexistence_pending_help') }}</dd>
                    </div>
                @endif
                @if ($isCoexistence)
                    <div>
                        <dt>{{ __('dashboard.whatsapp_onboarding_reconnect_note_label') }}</dt>
                        <dd>{{ __('dashboard.whatsapp_onboarding_coexistence_reconnect_note') }}</dd>
                    </div>
                @endif
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

        @if ($session?->status === 'awaiting_phone_selection' && count($availablePhones) > 0)
            <div style="margin-top:1rem;">
                <h2 style="font-size:1rem;margin:0 0 0.5rem;">{{ __('dashboard.whatsapp_onboarding_available_phones') }}</h2>
                <ul class="muted" style="margin:0;padding-inline-start:1.25rem;">
                    @foreach ($availablePhones as $phone)
                        <li>
                            {{ $phone['display_phone_number'] ?? '—' }}
                            <span>({{ $phone['id'] ?? '—' }})</span>
                            @if (! empty($phone['verified_name']))
                                — {{ $phone['verified_name'] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
                <p class="muted">{{ __('dashboard.whatsapp_onboarding_awaiting_phone_help') }}</p>
            </div>
        @endif

        <div class="actions">
            <a class="button" href="{{ $state->returnUrl }}">
                {{ $isSuccess ? __('dashboard.whatsapp_onboarding_return_numbers') : __('dashboard.whatsapp_onboarding_return_tenant') }}
            </a>

            @if ($canRetryFinalize)
                <form method="post" action="{{ route('whatsapp.onboarding.finalize') }}" style="display:inline;">
                    @csrf
                    <input type="hidden" name="state" value="{{ request('state') }}">
                    <button type="submit" class="button secondary">
                        {{ __('dashboard.whatsapp_onboarding_retry_finalize') }}
                    </button>
                </form>
            @endif

            @if (! $session || in_array($session->status, ['failed', 'cancelled', null], true))
                <a class="button secondary" href="{{ route('whatsapp.onboarding.start', ['state' => request('state')]) }}">
                    {{ __('dashboard.whatsapp_onboarding_launch_cta') }}
                </a>
            @endif
        </div>
    </div>
@endsection
