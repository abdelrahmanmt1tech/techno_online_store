@php
    /** @var \App\Models\WhatsAppWebhookEvent $event */
    $interpretation = is_array($event->interpretation) ? $event->interpretation : [];
    $details = $interpretation['details'] ?? [];
    $payload = $event->reprocessablePayload();
    $showRawPayload = $showRawPayload ?? false;
@endphp

<div class="wa-log-detail">
    <section class="wa-log-card">
        <h3 class="wa-log-card__title">{{ __('dashboard.whatsapp_log_summary') }}</h3>
        <p class="wa-log-card__summary">{{ $event->summary ?: ($interpretation['summary'] ?? '—') }}</p>
        <dl class="wa-log-grid">
            <div>
                <dt>{{ __('dashboard.whatsapp_processing_status') }}</dt>
                <dd>{{ $event->processing_status?->label() ?? '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_event_type') }}</dt>
                <dd>{{ $event->event_type ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_phone_number_id') }}</dt>
                <dd>{{ $event->phone_number_id ?: '—' }}</dd>
            </div>
            @if (isset($event->tenant))
                <div>
                    <dt>{{ __('dashboard.whatsapp_tenant') }}</dt>
                    <dd>{{ $event->tenant?->name ?: '—' }}</dd>
                </div>
            @endif
            <div>
                <dt>{{ __('dashboard.whatsapp_signature_valid') }}</dt>
                <dd>
                    @if ($event->signature_valid === true)
                        {{ __('dashboard.yes') }}
                    @elseif ($event->signature_valid === false)
                        {{ __('dashboard.no') }}
                    @else
                        —
                    @endif
                </dd>
            </div>
            <div>
                <dt>{{ __('dashboard.created_at') }}</dt>
                <dd>{{ $event->created_at?->toDateTimeString() ?: '—' }}</dd>
            </div>
            <div>
                <dt>{{ __('dashboard.whatsapp_processed_at') }}</dt>
                <dd>{{ $event->processed_at?->toDateTimeString() ?: '—' }}</dd>
            </div>
        </dl>
        @if (filled($event->error_message))
            <div class="wa-log-alert wa-log-alert--danger">
                <strong>{{ __('dashboard.whatsapp_error_details') }}</strong>
                <p>{{ $event->error_message }}</p>
            </div>
        @endif
    </section>

    @if ($details !== [])
        <section class="wa-log-card">
            <h3 class="wa-log-card__title">{{ __('dashboard.whatsapp_log_interpretation') }}</h3>
            @foreach ($details as $detail)
                <div class="wa-log-block">
                    <h4>{{ $detail['title'] ?? __('dashboard.whatsapp_webhook_other_event') }}</h4>
                    <dl class="wa-log-grid">
                        @foreach (($detail['items'] ?? []) as $label => $value)
                            <div>
                                <dt>{{ $label }}</dt>
                                <dd>{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach
        </section>
    @endif

    @if (is_array($event->diagnostic_data) && $event->diagnostic_data !== [])
        <section class="wa-log-card">
            <h3 class="wa-log-card__title">{{ __('dashboard.whatsapp_diagnostic_data') }}</h3>
            <pre class="wa-log-code">{{ json_encode($event->diagnostic_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    @endif

    @if ($showRawPayload && is_array($payload))
        <section class="wa-log-card">
            <h3 class="wa-log-card__title">{{ __('dashboard.whatsapp_payload') }}</h3>
            <pre class="wa-log-code">{{ json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    @endif
</div>
