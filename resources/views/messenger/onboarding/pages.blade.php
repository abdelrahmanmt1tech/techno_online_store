@extends('messenger.onboarding.layout')

@section('title', __('dashboard.messenger_onboarding_pick_pages'))

@section('content')
    <div class="card">
        <span class="badge">{{ __('dashboard.messenger_onboarding_picker_badge') }}</span>
        <h1>{{ __('dashboard.messenger_onboarding_pick_pages') }}</h1>
        <p class="muted">{{ __('dashboard.messenger_onboarding_pick_pages_intro') }}</p>

        @if (session('error'))
            <div class="alert">{{ session('error') }}</div>
        @endif

        @if ($pages === [])
            <div class="alert">{{ __('dashboard.messenger_onboarding_no_pages') }}</div>
            <div class="actions">
                <a class="button secondary" href="{{ $statusUrl }}">{{ __('dashboard.messenger_onboarding_status_link') }}</a>
                <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.messenger_onboarding_return') }}</a>
            </div>
        @else
            <form method="post" action="{{ route('messenger.onboarding.connect') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $stateToken }}">

                <ul class="page-list">
                    @foreach ($pages as $page)
                        @php
                            $pageId = $page['page_id'];
                            $already = in_array($pageId, $existingPageIds, true);
                            $checked = count($pages) === 1 || $already;
                        @endphp
                        <li class="page-item">
                            <input
                                type="checkbox"
                                id="page-{{ $pageId }}"
                                name="page_ids[]"
                                value="{{ $pageId }}"
                                @checked($checked)
                            >
                            <label for="page-{{ $pageId }}">
                                <strong>{{ $page['page_name'] ?: $pageId }}</strong>
                                <div class="page-meta">
                                    {{ __('dashboard.messenger_page_id') }}: <code>{{ $pageId }}</code>
                                    @if ($already)
                                        <span class="connected"> — {{ __('dashboard.messenger_onboarding_already_connected') }}</span>
                                    @endif
                                </div>
                            </label>
                        </li>
                    @endforeach
                </ul>

                <div class="actions">
                    <button type="submit" class="button">{{ __('dashboard.messenger_onboarding_connect_selected') }}</button>
                    <a class="button secondary" href="{{ $statusUrl }}">{{ __('dashboard.messenger_onboarding_status_link') }}</a>
                    <a class="button secondary" href="{{ $state->returnUrl }}">{{ __('dashboard.messenger_onboarding_return') }}</a>
                </div>
            </form>
        @endif
    </div>
@endsection
