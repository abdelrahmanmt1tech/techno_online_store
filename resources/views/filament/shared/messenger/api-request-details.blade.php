@php
    /** @var \App\Models\Tenant\MessengerApiRequest $request */
    $showTechnical = $showTechnical ?? false;
@endphp

<div class="wa-log-detail">
    <section class="wa-log-card">
        <h3 class="wa-log-card__title">{{ __('dashboard.messenger_log_summary') }}</h3>
        <p class="wa-log-card__summary">{{ $request->summary }}</p>
        <dl class="wa-log-grid">
            <div>
                <dt>{{ __('dashboard.messenger_api_operation') }}</dt>
                <dd>{{ $request->operation?->label() ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.messenger_api_outcome') }}</dt>
                <dd>{{ $request->outcome?->label() ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.messenger_api_status_label') }}</dt>
                <dd>{{ $request->status_label }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.messenger_recipient_psid') }}</dt>
                <dd>{{ $request->recipient_psid ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.messenger_page') }}</dt>
                <dd>{{ $request->messengerPage?->page_name ?: $request->messengerPage?->page_id ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.messenger_http_status') }}</dt>
                <dd>{{ $request->http_status ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.messenger_api_error_code') }}</dt>
                <dd>{{ $request->api_error_code ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.messenger_api_duration') }}</dt>
                <dd>{{ $request->duration_ms ? $request->duration_ms.' ms' : '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.created_at') }}</dt>
                <dd>{{ $request->created_at?->toDateTimeString() ?: '—' }}</dd>
            </div>
        </dl>
    </section>

    @if ($showTechnical)
        @if (is_array($request->request_payload))
            <section class="wa-log-card">
                <h3 class="wa-log-card__title">{{ __('dashboard.messenger_api_request_payload') }}</h3>
                <pre class="wa-log-code">{{ json_encode($request->request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </section>
        @endif

        @if (is_array($request->response_body))
            <section class="wa-log-card">
                <h3 class="wa-log-card__title">{{ __('dashboard.messenger_api_response_body') }}</h3>
                <pre class="wa-log-code">{{ json_encode($request->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </section>
        @endif
    @endif
</div>
