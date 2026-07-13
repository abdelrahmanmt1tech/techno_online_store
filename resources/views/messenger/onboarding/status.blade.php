@extends('messenger.onboarding.layout')

@section('title', __('dashboard.messenger_onboarding_status_title'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.messenger_onboarding_status_badge') }}</span>
        <h1>{{ __('dashboard.messenger_onboarding_status_title') }}</h1>
        <p class="muted">{{ $statusLabel }}</p>

        @if ($session?->last_error)
            <div class="alert">{{ $session->last_error }}</div>
        @endif

        <dl>
            <div>
                <dt>{{ __('dashboard.messenger_onboarding_session_status') }}</dt>
                <dd><code>{{ $session?->status ?? 'not_started' }}</code></dd>
            </div>
            @if ($connectedPageIds !== [])
                <div>
                    <dt>{{ __('dashboard.messenger_onboarding_connected_pages') }}</dt>
                    <dd>
                        @foreach ($connectedPageIds as $pageId)
                            <code>{{ $pageId }}</code>@if (! $loop->last), @endif
                        @endforeach
                    </dd>
                </div>
            @endif
        </dl>

        <div class="actions">
            @if (in_array($session?->status, ['awaiting_page_selection'], true))
                <a class="button" href="{{ route('messenger.onboarding.pages', ['state' => request('state')]) }}">
                    {{ __('dashboard.messenger_onboarding_pick_pages') }}
                </a>
            @endif
            @if (in_array($session?->status, [null, 'failed', 'cancelled'], true) || $session === null)
                <a class="button" href="{{ route('messenger.onboarding.start', ['state' => request('state')]) }}">
                    {{ __('dashboard.messenger_onboarding_retry_login') }}
                </a>
            @endif
            <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.messenger_onboarding_return') }}</a>
        </div>
    </div>
@endsection
