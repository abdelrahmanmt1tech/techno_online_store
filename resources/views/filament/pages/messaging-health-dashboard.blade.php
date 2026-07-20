<x-filament-panels::page>
    <div class="mh-dashboard">
        <div class="mh-alert" role="note">
            <p class="mh-alert__title">{{ __('dashboard.messaging_health_ops_note_title') }}</p>
            <ul class="mh-alert__list">
                <li>{{ __('dashboard.messaging_health_ops_note_whatsapp') }}</li>
                <li>{{ __('dashboard.messaging_health_ops_note_messenger') }}</li>
                <li>{{ __('dashboard.messaging_health_ops_note_scope') }}</li>
            </ul>
        </div>

        <div class="mh-toolbar">
            <div>
                <h2 class="mh-toolbar__title">{{ __('dashboard.messaging_health_overview') }}</h2>
                <p class="mh-toolbar__subtitle">{{ __('dashboard.messaging_health_overview_help') }}</p>
            </div>
            <x-filament::button wire:click="refreshDashboard" color="gray" icon="heroicon-o-arrow-path">
                {{ __('dashboard.messaging_health_refresh') }}
            </x-filament::button>
        </div>

        @php($summary = $this->summary)
        @php($tenants = $summary['tenants'])
        @php($wa = $summary['whatsapp'])
        @php($ms = $summary['messenger'])
        @php($wh = $summary['webhooks'])

        <div class="mh-kpi-grid">
            <div class="mh-kpi">
                <div class="mh-kpi__label">{{ __('dashboard.messaging_health_tenants_with_messaging') }}</div>
                <div class="mh-kpi__value">{{ $tenants['tenants_with_messaging'] }}</div>
            </div>
            <div class="mh-kpi">
                <div class="mh-kpi__label">{{ __('dashboard.messaging_health_whatsapp_only') }}</div>
                <div class="mh-kpi__value">{{ $tenants['whatsapp_only'] }}</div>
            </div>
            <div class="mh-kpi">
                <div class="mh-kpi__label">{{ __('dashboard.messaging_health_messenger_only') }}</div>
                <div class="mh-kpi__value">{{ $tenants['messenger_only'] }}</div>
            </div>
            <div class="mh-kpi">
                <div class="mh-kpi__label">{{ __('dashboard.messaging_health_both_channels') }}</div>
                <div class="mh-kpi__value">{{ $tenants['both'] }}</div>
            </div>
            <div class="mh-kpi mh-kpi--danger">
                <div class="mh-kpi__label">{{ __('dashboard.messaging_health_needing_attention') }}</div>
                <div class="mh-kpi__value">{{ $summary['attention_count'] }}</div>
            </div>
        </div>

        <div class="mh-channel-grid">
            <section class="mh-panel">
                <h3 class="mh-panel__title mh-panel__title--wa">{{ __('dashboard.messaging_health_whatsapp_summary') }}</h3>
                <div class="mh-metrics">
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_total') }}</span><span class="mh-metric__value">{{ $wa['total'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_active') }}</span><span class="mh-metric__value">{{ $wa['active'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_reconnect') }}</span><span class="mh-metric__value">{{ $wa['reconnect_required'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_failed') }}</span><span class="mh-metric__value">{{ $wa['failed'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_disabled') }}</span><span class="mh-metric__value">{{ $wa['disabled'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_webhook_subscribed') }}</span><span class="mh-metric__value">{{ $wa['webhook_subscribed'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_pending_onboarding') }}</span><span class="mh-metric__value">{{ $wa['pending_onboarding'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_method_manual') }}</span><span class="mh-metric__value">{{ $wa['method_manual'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_method_api_only') }}</span><span class="mh-metric__value">{{ $wa['method_api_only'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_method_coexistence') }}</span><span class="mh-metric__value">{{ $wa['method_coexistence'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_unresolved_webhooks') }}</span><span class="mh-metric__value">{{ $wh['whatsapp']['unresolved'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_failed_webhooks') }}</span><span class="mh-metric__value">{{ $wh['whatsapp']['failed'] }}</span></div>
                </div>
            </section>

            <section class="mh-panel">
                <h3 class="mh-panel__title mh-panel__title--ms">{{ __('dashboard.messaging_health_messenger_summary') }}</h3>
                <div class="mh-metrics">
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_total') }}</span><span class="mh-metric__value">{{ $ms['total'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_active') }}</span><span class="mh-metric__value">{{ $ms['active'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_reconnect') }}</span><span class="mh-metric__value">{{ $ms['reconnect_required'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_failed') }}</span><span class="mh-metric__value">{{ $ms['failed'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_disabled') }}</span><span class="mh-metric__value">{{ $ms['disabled'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_webhook_subscribed') }}</span><span class="mh-metric__value">{{ $ms['webhook_subscribed'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_method_manual') }}</span><span class="mh-metric__value">{{ $ms['method_manual'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_method_facebook_login') }}</span><span class="mh-metric__value">{{ $ms['method_facebook_login'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_unresolved_webhooks') }}</span><span class="mh-metric__value">{{ $wh['messenger']['unresolved'] }}</span></div>
                    <div class="mh-metric"><span class="mh-metric__label">{{ __('dashboard.messaging_health_failed_webhooks') }}</span><span class="mh-metric__value">{{ $wh['messenger']['failed'] }}</span></div>
                </div>
            </section>
        </div>

        <section class="mh-panel">
            <div class="mh-panel__head">
                <div>
                    <h3 class="mh-panel__title">{{ __('dashboard.messaging_health_webhook_panel') }}</h3>
                    <p class="mh-panel__hint">{{ __('dashboard.messaging_health_webhook_panel_help') }}</p>
                </div>
                <div class="mh-period">
                    <label for="webhookPeriodHours">{{ __('dashboard.messaging_health_period') }}</label>
                    <select id="webhookPeriodHours" wire:model.live="webhookPeriodHours" class="mh-select">
                        <option value="24">24h</option>
                        <option value="168">7d</option>
                        <option value="720">30d</option>
                    </select>
                </div>
            </div>
            <div class="mh-webhook-grid">
                <div class="mh-webhook-card">
                    <div class="mh-webhook-card__head">
                        <span>WhatsApp</span>
                        <a class="mh-link" href="{{ $this->webhookEventsUrl('whatsapp') }}">{{ __('dashboard.messaging_health_open_events') }}</a>
                    </div>
                    <div class="mh-webhook-stats">
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_processed') }}: <strong>{{ $wh['whatsapp']['processed'] }}</strong></div>
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_failed') }}: <strong>{{ $wh['whatsapp']['failed'] }}</strong></div>
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_unresolved') }}: <strong>{{ $wh['whatsapp']['unresolved'] }}</strong></div>
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_rejected') }}: <strong>{{ $wh['whatsapp']['rejected'] }}</strong></div>
                    </div>
                </div>
                <div class="mh-webhook-card">
                    <div class="mh-webhook-card__head">
                        <span>Messenger</span>
                        <a class="mh-link" href="{{ $this->webhookEventsUrl('messenger') }}">{{ __('dashboard.messaging_health_open_events') }}</a>
                    </div>
                    <div class="mh-webhook-stats">
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_processed') }}: <strong>{{ $wh['messenger']['processed'] }}</strong></div>
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_failed') }}: <strong>{{ $wh['messenger']['failed'] }}</strong></div>
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_unresolved') }}: <strong>{{ $wh['messenger']['unresolved'] }}</strong></div>
                        <div class="mh-webhook-stat">{{ __('dashboard.messaging_health_rejected') }}: <strong>{{ $wh['messenger']['rejected'] }}</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mh-panel">
            <h3 class="mh-panel__title">{{ __('dashboard.messaging_health_attention_title') }}</h3>

            <div class="mh-filters">
                <select wire:model.live="filterChannel" class="mh-select">
                    <option value="">{{ __('dashboard.messaging_health_filter_channel') }}</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="messenger">Messenger</option>
                </select>
                <select wire:model.live="filterTenantId" class="mh-select">
                    <option value="">{{ __('dashboard.messaging_health_filter_tenant') }}</option>
                    @foreach ($this->tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterHealth" class="mh-select">
                    <option value="">{{ __('dashboard.messaging_health_filter_health') }}</option>
                    @foreach ($this->healthOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterStatus" class="mh-select">
                    <option value="">{{ __('dashboard.messaging_health_filter_status') }}</option>
                    <option value="active">active</option>
                    <option value="disabled">disabled</option>
                    <option value="reconnect_required">reconnect_required</option>
                    <option value="failed">failed</option>
                </select>
                <select wire:model.live="filterWebhookStatus" class="mh-select">
                    <option value="">{{ __('dashboard.messaging_health_filter_webhook') }}</option>
                    <option value="subscribed">subscribed</option>
                    <option value="pending">pending</option>
                    <option value="failed">failed</option>
                </select>
                <input wire:model.live.debounce.400ms="filterSearch" type="search" placeholder="{{ __('dashboard.messaging_health_search') }}" class="mh-input">
            </div>

            <label class="mh-checkbox-row">
                <input type="checkbox" wire:model.live="needsAttentionOnly">
                {{ __('dashboard.messaging_health_needs_attention_only') }}
            </label>

            <div class="mh-table-wrap">
                <table class="mh-table">
                    <thead>
                        <tr>
                            <th>{{ __('dashboard.messaging_health_col_channel') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_tenant') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_asset') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_method') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_status') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_webhook') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_health') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_inbound') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_outbound') }}</th>
                            <th>{{ __('dashboard.messaging_health_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->attentionRows as $row)
                            <tr wire:key="{{ $row['key'] }}">
                                <td>
                                    <span class="mh-channel-pill mh-channel-pill--{{ $row['channel'] }}">
                                        {{ strtoupper($row['channel']) }}
                                    </span>
                                </td>
                                <td>{{ $row['tenant_name'] }}</td>
                                <td>
                                    <div class="mh-table__asset-name">{{ $row['asset_name'] }}</div>
                                    <div class="mh-table__asset-id">{{ $row['asset_id'] }}</div>
                                </td>
                                <td>{{ $row['connection_method'] ?? '—' }}</td>
                                <td>{{ $row['status'] ?? '—' }}</td>
                                <td>{{ $row['webhook_status'] ?? '—' }}</td>
                                <td>
                                    <span class="mh-badge mh-badge--{{ $row['health'] }}">{{ $row['health_label'] }}</span>
                                </td>
                                <td class="mh-table__nowrap">{{ $row['last_inbound_at'] ?? '—' }}</td>
                                <td class="mh-table__nowrap">{{ $row['last_outbound_at'] ?? '—' }}</td>
                                <td>
                                    <div class="mh-actions">
                                        <a class="mh-link" href="{{ $this->registryUrl($row['channel']) }}">{{ __('dashboard.messaging_health_open_registry') }}</a>
                                        <button type="button" class="mh-btn-link" wire:click="inspectConnection('{{ $row['channel'] }}', {{ $row['registry_id'] }})">
                                            {{ __('dashboard.messaging_health_inspect') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="mh-table__empty">
                                    {{ __('dashboard.messaging_health_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($inspection)
            <div class="mh-modal-backdrop" wire:click.self="closeInspection" role="dialog" aria-modal="true">
                <div class="mh-modal">
                    <div class="mh-modal__head">
                        <h3 class="mh-modal__title">{{ __('dashboard.messaging_health_inspect_title') }}</h3>
                        <button type="button" class="mh-modal__close" wire:click="closeInspection">{{ __('dashboard.close') }}</button>
                    </div>
                    <dl class="mh-dl">
                        <div><dt>{{ __('dashboard.messaging_health_col_channel') }}</dt><dd>{{ $inspection['channel'] }}</dd></div>
                        <div><dt>{{ __('dashboard.messaging_health_col_tenant') }}</dt><dd>{{ $inspection['tenant_name'] }}</dd></div>
                        <div><dt>Tenant ID</dt><dd class="mh-table__asset-id">{{ $inspection['tenant_id'] }}</dd></div>
                        <div><dt>{{ __('dashboard.messaging_health_col_status') }}</dt><dd>{{ $inspection['status'] }}</dd></div>
                        <div><dt>{{ __('dashboard.messaging_health_col_webhook') }}</dt><dd>{{ $inspection['webhook_status'] }}</dd></div>
                        <div><dt>{{ __('dashboard.messaging_health_col_method') }}</dt><dd>{{ $inspection['connection_method'] }}</dd></div>
                        <div><dt>{{ __('dashboard.messaging_health_token_configured') }}</dt><dd>{{ ($inspection['tenant_connection']['token_configured'] ?? false) ? __('dashboard.yes') : __('dashboard.no') }}</dd></div>
                        <div><dt>{{ __('dashboard.messaging_health_token_source') }}</dt><dd>{{ $inspection['tenant_connection']['token_source'] ?? '—' }}</dd></div>
                        <div class="mh-dl__wide"><dt>{{ __('dashboard.messaging_health_last_error') }}</dt><dd>{{ $inspection['tenant_connection']['last_error_message'] ?? '—' }}</dd></div>
                    </dl>
                    <p class="mh-modal__note">{{ __('dashboard.messaging_health_inspect_safe_note') }}</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
