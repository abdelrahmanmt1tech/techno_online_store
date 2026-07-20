<x-filament-panels::page>
    <div class="mr-page">
        <div class="mr-badges">
            <span class="mr-badge mr-badge--danger">{{ __('dashboard.meta_reset_badge_dangerous') }}</span>
            <span class="mr-badge mr-badge--info">{{ __('dashboard.meta_reset_badge_local') }}</span>
            <span class="mr-badge mr-badge--ok">{{ __('dashboard.meta_reset_badge_external') }}</span>
        </div>

        @unless ($this->featureEnabled)
            <div class="mr-alert mr-alert--blocked">
                <strong>{{ __('dashboard.meta_reset_disabled_title') }}</strong>
                <p>{{ __('dashboard.meta_reset_disabled_body') }}</p>
            </div>
        @else
            <div class="mr-alert mr-alert--danger">
                <strong>{{ __('dashboard.meta_reset_warning_title') }}</strong>
                <p>{{ __('dashboard.meta_reset_warning_body') }}</p>
                <ul>
                    <li>{{ __('dashboard.meta_reset_preserved_note') }}</li>
                    <li>{{ __('dashboard.meta_reset_external_note') }}</li>
                    <li>{{ __('dashboard.meta_reset_after_note') }}</li>
                </ul>
            </div>

            <section class="mr-panel">
                <h3 class="mr-panel__title">{{ __('dashboard.meta_reset_scope_title') }}</h3>
                <div class="mr-scope">
                    <label class="mr-radio">
                        <input type="radio" wire:model.live="scope" value="all">
                        <span>{{ __('dashboard.meta_reset_scope_all') }}</span>
                    </label>
                    <label class="mr-radio">
                        <input type="radio" wire:model.live="scope" value="whatsapp">
                        <span>{{ __('dashboard.meta_reset_scope_whatsapp') }}</span>
                    </label>
                    <label class="mr-radio">
                        <input type="radio" wire:model.live="scope" value="messenger">
                        <span>{{ __('dashboard.meta_reset_scope_messenger') }}</span>
                    </label>
                </div>
                <div class="mr-actions">
                    <x-filament::button wire:click="runPreview" color="gray" icon="heroicon-o-eye" :disabled="$scope === ''">
                        {{ __('dashboard.meta_reset_preview_btn') }}
                    </x-filament::button>
                </div>
            </section>

            @if ($preview)
                <section class="mr-panel">
                    <h3 class="mr-panel__title">{{ __('dashboard.meta_reset_preview_title') }}</h3>
                    <p class="mr-muted">
                        {{ __('dashboard.meta_reset_preview_at') }}: {{ $preview['previewed_at'] ?? '—' }}
                        · {{ __('dashboard.meta_reset_preview_expires') }}: {{ $preview['expires_at'] ?? '—' }}
                        · {{ __('dashboard.meta_reset_scope_label') }}: {{ $preview['scope'] ?? '—' }}
                    </p>

                    <h4 class="mr-subtitle">{{ __('dashboard.meta_reset_central_tables') }}</h4>
                    <div class="mr-table-wrap">
                        <table class="mr-table">
                            <thead>
                                <tr>
                                    <th>{{ __('dashboard.meta_reset_col_table') }}</th>
                                    <th>{{ __('dashboard.meta_reset_col_channel') }}</th>
                                    <th>{{ __('dashboard.meta_reset_col_order') }}</th>
                                    <th>{{ __('dashboard.meta_reset_col_rows') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($preview['central']['tables'] ?? []) as $row)
                                    <tr>
                                        <td><code>{{ $row['table'] }}</code></td>
                                        <td>{{ $row['channel'] }}</td>
                                        <td>{{ $row['deletion_order'] }}</td>
                                        <td>{{ $row['row_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mr-muted">{{ __('dashboard.meta_reset_central_total') }}: {{ $preview['central']['total_rows'] ?? 0 }}</p>

                    <h4 class="mr-subtitle">{{ __('dashboard.meta_reset_tenant_tables') }}</h4>
                    <p class="mr-muted">
                        {{ __('dashboard.meta_reset_tenants_total') }}: {{ $preview['tenants']['tenants_total'] ?? 0 }}
                        · {{ __('dashboard.meta_reset_tenants_inspected') }}: {{ $preview['tenants']['tenants_inspected'] ?? 0 }}
                        · {{ __('dashboard.meta_reset_tenants_failed') }}: {{ $preview['tenants']['tenants_failed'] ?? 0 }}
                        · {{ __('dashboard.meta_reset_tenant_total_rows') }}: {{ $preview['tenants']['total_rows'] ?? 0 }}
                    </p>
                    <div class="mr-table-wrap">
                        <table class="mr-table">
                            <thead>
                                <tr>
                                    <th>{{ __('dashboard.meta_reset_col_table') }}</th>
                                    <th>{{ __('dashboard.meta_reset_col_channel') }}</th>
                                    <th>{{ __('dashboard.meta_reset_col_order') }}</th>
                                    <th>{{ __('dashboard.meta_reset_col_rows') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (($preview['tenants']['tables'] ?? []) as $row)
                                    <tr>
                                        <td><code>{{ $row['table'] }}</code></td>
                                        <td>{{ $row['channel'] }}</td>
                                        <td>{{ $row['deletion_order'] }}</td>
                                        <td>{{ $row['row_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <details class="mr-details">
                        <summary>{{ __('dashboard.meta_reset_per_tenant') }}</summary>
                        <div class="mr-table-wrap">
                            <table class="mr-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('dashboard.meta_reset_col_tenant') }}</th>
                                        <th>{{ __('dashboard.meta_reset_col_rows') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (($preview['tenants']['per_tenant'] ?? []) as $tenantRow)
                                        <tr>
                                            <td>{{ $tenantRow['tenant_name'] }} <span class="mr-muted">({{ $tenantRow['tenant_id'] }})</span></td>
                                            <td>{{ $tenantRow['total_rows'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>

                    <h4 class="mr-subtitle">{{ __('dashboard.meta_reset_will_not_delete') }}</h4>
                    <ul class="mr-list">
                        @foreach ($this->preservedExamples as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </section>

                <section class="mr-panel mr-panel--danger">
                    <h3 class="mr-panel__title">{{ __('dashboard.meta_reset_confirm_title') }}</h3>
                    <label class="mr-check">
                        <input type="checkbox" wire:model.live="confirmChecked">
                        <span>{{ __('dashboard.meta_reset_confirm_checkbox') }}</span>
                    </label>
                    <label class="mr-label" for="confirmationPhrase">{{ __('dashboard.meta_reset_confirm_type', ['phrase' => $this->confirmationPhrase]) }}</label>
                    <input id="confirmationPhrase" type="text" class="mr-input" wire:model.live="confirmationPhrase" autocomplete="off">

                    <div class="mr-actions">
                        <x-filament::button
                            wire:click="executeReset"
                            color="danger"
                            icon="heroicon-o-trash"
                            :disabled="! $this->canExecute()"
                            wire:confirm="{{ __('dashboard.meta_reset_modal_body') }}"
                        >
                            {{ __('dashboard.meta_reset_execute_btn') }}
                        </x-filament::button>
                    </div>
                </section>
            @endif

            @if ($result)
                <section class="mr-panel">
                    <h3 class="mr-panel__title">{{ __('dashboard.meta_reset_result_title') }}</h3>
                    <p>
                        <strong>{{ __('dashboard.meta_reset_col_status') }}:</strong>
                        {{ __('dashboard.meta_reset_status_'.($result['status'] ?? 'failed')) }}
                    </p>
                    <ul class="mr-list">
                        <li>{{ __('dashboard.meta_reset_central_deleted') }}: {{ $result['central_rows_deleted'] ?? 0 }}</li>
                        <li>{{ __('dashboard.meta_reset_tenant_deleted') }}: {{ $result['tenant_rows_deleted'] ?? 0 }}</li>
                        <li>{{ __('dashboard.meta_reset_tenants_succeeded') }}: {{ $result['tenants_succeeded'] ?? 0 }}</li>
                        <li>{{ __('dashboard.meta_reset_tenants_failed') }}: {{ $result['tenants_failed'] ?? 0 }}</li>
                        <li>{{ __('dashboard.meta_reset_external_unchanged') }}</li>
                    </ul>
                    @if (! empty($result['errors']))
                        <h4 class="mr-subtitle">{{ __('dashboard.meta_reset_errors') }}</h4>
                        <ul class="mr-list mr-list--errors">
                            @foreach ($result['errors'] as $error)
                                <li>{{ is_array($error) ? ($error['message'] ?? json_encode($error)) : $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <details class="mr-details">
                        <summary>{{ __('dashboard.meta_reset_copy_summary') }}</summary>
                        <pre class="mr-pre">{{ json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </details>
                </section>
            @endif
        @endunless

        <section class="mr-panel">
            <h3 class="mr-panel__title">{{ __('dashboard.meta_reset_history_title') }}</h3>
            <div class="mr-table-wrap">
                <table class="mr-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>{{ __('dashboard.meta_reset_scope_label') }}</th>
                            <th>{{ __('dashboard.meta_reset_col_status') }}</th>
                            <th>{{ __('dashboard.meta_reset_central_deleted') }}</th>
                            <th>{{ __('dashboard.meta_reset_tenant_deleted') }}</th>
                            <th>{{ __('dashboard.meta_reset_col_when') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($this->recentRuns as $run)
                            <tr>
                                <td>{{ $run->id }}</td>
                                <td>{{ $run->scope }}</td>
                                <td>{{ $run->status }}</td>
                                <td>{{ $run->central_rows_deleted }}</td>
                                <td>{{ $run->tenant_rows_deleted }}</td>
                                <td>{{ optional($run->completed_at ?? $run->created_at)?->toDateTimeString() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="mr-empty">{{ __('dashboard.meta_reset_history_empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
