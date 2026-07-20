<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-amber-300/60 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-500/30 dark:bg-amber-950/30 dark:text-amber-100">
            <p class="font-semibold">{{ __('dashboard.messaging_health_ops_note_title') }}</p>
            <ul class="mt-2 list-disc space-y-1 ps-5">
                <li>{{ __('dashboard.messaging_health_ops_note_whatsapp') }}</li>
                <li>{{ __('dashboard.messaging_health_ops_note_messenger') }}</li>
                <li>{{ __('dashboard.messaging_health_ops_note_scope') }}</li>
            </ul>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ __('dashboard.messaging_health_overview') }}</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('dashboard.messaging_health_overview_help') }}</p>
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

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('dashboard.messaging_health_tenants_with_messaging') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ $tenants['tenants_with_messaging'] }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('dashboard.messaging_health_whatsapp_only') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ $tenants['whatsapp_only'] }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('dashboard.messaging_health_messenger_only') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ $tenants['messenger_only'] }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('dashboard.messaging_health_both_channels') }}</div>
                <div class="mt-2 text-2xl font-bold">{{ $tenants['both'] }}</div>
            </div>
            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/30 dark:bg-rose-950/20">
                <div class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-300">{{ __('dashboard.messaging_health_needing_attention') }}</div>
                <div class="mt-2 text-2xl font-bold text-rose-700 dark:text-rose-200">{{ $summary['attention_count'] }}</div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <h3 class="mb-3 text-sm font-semibold">{{ __('dashboard.messaging_health_whatsapp_summary') }}</h3>
                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_total') }}</span><div class="font-semibold">{{ $wa['total'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_active') }}</span><div class="font-semibold">{{ $wa['active'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_reconnect') }}</span><div class="font-semibold">{{ $wa['reconnect_required'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_failed') }}</span><div class="font-semibold">{{ $wa['failed'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_disabled') }}</span><div class="font-semibold">{{ $wa['disabled'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_webhook_subscribed') }}</span><div class="font-semibold">{{ $wa['webhook_subscribed'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_pending_onboarding') }}</span><div class="font-semibold">{{ $wa['pending_onboarding'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_method_manual') }}</span><div class="font-semibold">{{ $wa['method_manual'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_method_api_only') }}</span><div class="font-semibold">{{ $wa['method_api_only'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_method_coexistence') }}</span><div class="font-semibold">{{ $wa['method_coexistence'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_unresolved_webhooks') }}</span><div class="font-semibold">{{ $wh['whatsapp']['unresolved'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_failed_webhooks') }}</span><div class="font-semibold">{{ $wh['whatsapp']['failed'] }}</div></div>
                </div>
            </section>

            <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <h3 class="mb-3 text-sm font-semibold">{{ __('dashboard.messaging_health_messenger_summary') }}</h3>
                <div class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-3">
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_total') }}</span><div class="font-semibold">{{ $ms['total'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_active') }}</span><div class="font-semibold">{{ $ms['active'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_reconnect') }}</span><div class="font-semibold">{{ $ms['reconnect_required'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_failed') }}</span><div class="font-semibold">{{ $ms['failed'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_disabled') }}</span><div class="font-semibold">{{ $ms['disabled'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_webhook_subscribed') }}</span><div class="font-semibold">{{ $ms['webhook_subscribed'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_method_manual') }}</span><div class="font-semibold">{{ $ms['method_manual'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_method_facebook_login') }}</span><div class="font-semibold">{{ $ms['method_facebook_login'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_unresolved_webhooks') }}</span><div class="font-semibold">{{ $wh['messenger']['unresolved'] }}</div></div>
                    <div><span class="text-gray-500">{{ __('dashboard.messaging_health_failed_webhooks') }}</span><div class="font-semibold">{{ $wh['messenger']['failed'] }}</div></div>
                </div>
            </section>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold">{{ __('dashboard.messaging_health_webhook_panel') }}</h3>
                    <p class="text-xs text-gray-500">{{ __('dashboard.messaging_health_webhook_panel_help') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-gray-500" for="webhookPeriodHours">{{ __('dashboard.messaging_health_period') }}</label>
                    <select id="webhookPeriodHours" wire:model.live="webhookPeriodHours" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                        <option value="24">24h</option>
                        <option value="168">7d</option>
                        <option value="720">30d</option>
                    </select>
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div class="rounded-lg border border-gray-100 p-3 dark:border-white/10">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-medium">WhatsApp</span>
                        <a class="text-xs text-primary-600" href="{{ $this->webhookEventsUrl('whatsapp') }}">{{ __('dashboard.messaging_health_open_events') }}</a>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>{{ __('dashboard.messaging_health_processed') }}: <strong>{{ $wh['whatsapp']['processed'] }}</strong></div>
                        <div>{{ __('dashboard.messaging_health_failed') }}: <strong>{{ $wh['whatsapp']['failed'] }}</strong></div>
                        <div>{{ __('dashboard.messaging_health_unresolved') }}: <strong>{{ $wh['whatsapp']['unresolved'] }}</strong></div>
                        <div>{{ __('dashboard.messaging_health_rejected') }}: <strong>{{ $wh['whatsapp']['rejected'] }}</strong></div>
                    </div>
                </div>
                <div class="rounded-lg border border-gray-100 p-3 dark:border-white/10">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="font-medium">Messenger</span>
                        <a class="text-xs text-primary-600" href="{{ $this->webhookEventsUrl('messenger') }}">{{ __('dashboard.messaging_health_open_events') }}</a>
                    </div>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div>{{ __('dashboard.messaging_health_processed') }}: <strong>{{ $wh['messenger']['processed'] }}</strong></div>
                        <div>{{ __('dashboard.messaging_health_failed') }}: <strong>{{ $wh['messenger']['failed'] }}</strong></div>
                        <div>{{ __('dashboard.messaging_health_unresolved') }}: <strong>{{ $wh['messenger']['unresolved'] }}</strong></div>
                        <div>{{ __('dashboard.messaging_health_rejected') }}: <strong>{{ $wh['messenger']['rejected'] }}</strong></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <h3 class="mb-3 text-sm font-semibold">{{ __('dashboard.messaging_health_attention_title') }}</h3>

            <div class="mb-4 grid gap-2 md:grid-cols-3 xl:grid-cols-6">
                <select wire:model.live="filterChannel" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                    <option value="">{{ __('dashboard.messaging_health_filter_channel') }}</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="messenger">Messenger</option>
                </select>
                <select wire:model.live="filterTenantId" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                    <option value="">{{ __('dashboard.messaging_health_filter_tenant') }}</option>
                    @foreach ($this->tenants as $tenant)
                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterHealth" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                    <option value="">{{ __('dashboard.messaging_health_filter_health') }}</option>
                    @foreach ($this->healthOptions as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                    <option value="">{{ __('dashboard.messaging_health_filter_status') }}</option>
                    <option value="active">active</option>
                    <option value="disabled">disabled</option>
                    <option value="reconnect_required">reconnect_required</option>
                    <option value="failed">failed</option>
                </select>
                <select wire:model.live="filterWebhookStatus" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
                    <option value="">{{ __('dashboard.messaging_health_filter_webhook') }}</option>
                    <option value="subscribed">subscribed</option>
                    <option value="pending">pending</option>
                    <option value="failed">failed</option>
                </select>
                <input wire:model.live.debounce.400ms="filterSearch" type="search" placeholder="{{ __('dashboard.messaging_health_search') }}" class="rounded-lg border-gray-300 text-sm dark:border-white/10 dark:bg-gray-900">
            </div>

            <label class="mb-3 inline-flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="needsAttentionOnly" class="rounded border-gray-300">
                {{ __('dashboard.messaging_health_needs_attention_only') }}
            </label>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5">
                        <tr>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_channel') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_tenant') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_asset') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_method') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_status') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_webhook') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_health') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_inbound') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_outbound') }}</th>
                            <th class="px-3 py-2">{{ __('dashboard.messaging_health_col_actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @forelse ($this->attentionRows as $row)
                            <tr wire:key="{{ $row['key'] }}">
                                <td class="px-3 py-2">{{ strtoupper($row['channel']) }}</td>
                                <td class="px-3 py-2">{{ $row['tenant_name'] }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-medium">{{ $row['asset_name'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $row['asset_id'] }}</div>
                                </td>
                                <td class="px-3 py-2">{{ $row['connection_method'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row['status'] ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row['webhook_status'] ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-emerald-100 text-emerald-800' => $row['health'] === 'healthy',
                                        'bg-amber-100 text-amber-800' => $row['health'] === 'warning' || $row['health'] === 'pending',
                                        'bg-rose-100 text-rose-800' => in_array($row['health'], ['failed', 'reconnect_required'], true),
                                        'bg-gray-100 text-gray-700' => in_array($row['health'], ['disabled', 'unknown'], true),
                                    ])>{{ $row['health_label'] }}</span>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['last_inbound_at'] ?? '—' }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $row['last_outbound_at'] ?? '—' }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-col gap-1">
                                        <a class="text-xs text-primary-600" href="{{ $this->registryUrl($row['channel']) }}">{{ __('dashboard.messaging_health_open_registry') }}</a>
                                        <button type="button" class="text-left text-xs text-primary-600" wire:click="inspectConnection('{{ $row['channel'] }}', {{ $row['registry_id'] }})">
                                            {{ __('dashboard.messaging_health_inspect') }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-3 py-8 text-center text-gray-500">
                                    {{ __('dashboard.messaging_health_empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        @if ($inspection)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="closeInspection">
                <div class="max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900">
                    <div class="mb-3 flex items-start justify-between gap-3">
                        <h3 class="text-base font-semibold">{{ __('dashboard.messaging_health_inspect_title') }}</h3>
                        <button type="button" class="text-sm text-gray-500" wire:click="closeInspection">{{ __('dashboard.close') }}</button>
                    </div>
                    <dl class="grid gap-2 text-sm sm:grid-cols-2">
                        <div><dt class="text-gray-500">{{ __('dashboard.messaging_health_col_channel') }}</dt><dd class="font-medium">{{ $inspection['channel'] }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('dashboard.messaging_health_col_tenant') }}</dt><dd class="font-medium">{{ $inspection['tenant_name'] }}</dd></div>
                        <div><dt class="text-gray-500">Tenant ID</dt><dd class="font-mono text-xs">{{ $inspection['tenant_id'] }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('dashboard.messaging_health_col_status') }}</dt><dd class="font-medium">{{ $inspection['status'] }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('dashboard.messaging_health_col_webhook') }}</dt><dd class="font-medium">{{ $inspection['webhook_status'] }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('dashboard.messaging_health_col_method') }}</dt><dd class="font-medium">{{ $inspection['connection_method'] }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('dashboard.messaging_health_token_configured') }}</dt><dd class="font-medium">{{ ($inspection['tenant_connection']['token_configured'] ?? false) ? __('dashboard.yes') : __('dashboard.no') }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('dashboard.messaging_health_token_source') }}</dt><dd class="font-medium">{{ $inspection['tenant_connection']['token_source'] ?? '—' }}</dd></div>
                        <div class="sm:col-span-2"><dt class="text-gray-500">{{ __('dashboard.messaging_health_last_error') }}</dt><dd class="font-medium">{{ $inspection['tenant_connection']['last_error_message'] ?? '—' }}</dd></div>
                    </dl>
                    <p class="mt-4 text-xs text-gray-500">{{ __('dashboard.messaging_health_inspect_safe_note') }}</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
