<x-filament-panels::page>
    @php($activePackage = $this->getActivePackage())
    @php($themeSubscription = $this->getThemeSubscription())
    @php($statusModifier = static fn (string $status): string => 'subscriptions-badge--'.(match ($status) {
        'trial', 'active', 'expired', 'cancelled' => $status,
        default => 'cancelled',
    }))

    <div class="subscriptions-page">
        {{-- Current plan --}}
        <section class="subscriptions-card">
            <h2 class="subscriptions-card__title">{{ __('dashboard.current_plan') }}</h2>

            @if ($activePackage)
                <div class="subscriptions-plan">
                    <span class="subscriptions-plan__name">{{ $activePackage->package?->name }}</span>
                    <span class="subscriptions-badge {{ $statusModifier($activePackage->status) }}">
                        {{ __('dashboard.status_'.$activePackage->status) }}
                    </span>
                </div>

                <dl class="subscriptions-grid subscriptions-grid--plan">
                    <div>
                        <dt class="subscriptions-detail__label">{{ __('dashboard.price') }}</dt>
                        <dd class="subscriptions-detail__value">
                            {{ number_format((float) $activePackage->price, 2) }} {{ $activePackage->currency?->code }}
                        </dd>
                    </div>
                    <div>
                        <dt class="subscriptions-detail__label">{{ __('dashboard.duration') }}</dt>
                        <dd class="subscriptions-detail__value">{{ $this->formatDuration($activePackage) }}</dd>
                    </div>
                    <div>
                        <dt class="subscriptions-detail__label">{{ __('dashboard.period') }}</dt>
                        <dd class="subscriptions-detail__value">
                            {{ $activePackage->started_at?->toDateString() }}
                            @if ($activePackage->expires_at)
                                → {{ $activePackage->expires_at->toDateString() }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="subscriptions-detail__label">{{ __('dashboard.trial_ends_at') }}</dt>
                        <dd class="subscriptions-detail__value">
                            {{ $activePackage->trial_ends_at?->toDateString() ?? '—' }}
                        </dd>
                    </div>
                </dl>

                @php($enabledModules = $this->getEnabledModules())
                @if ($enabledModules)
                    <div class="subscriptions-modules">
                        <span class="subscriptions-modules__label">{{ __('dashboard.enabled_modules') }}</span>
                        <div class="subscriptions-modules__list">
                            @foreach ($enabledModules as $module)
                                <span class="subscriptions-module-chip">{{ $module }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <p class="subscriptions-empty">{{ __('dashboard.no_active_package') }}</p>
            @endif
        </section>

        {{-- Theme subscription --}}
        <section class="subscriptions-card">
            <h2 class="subscriptions-card__title">{{ __('dashboard.theme_subscription') }}</h2>

            @if ($themeSubscription)
                <div class="subscriptions-plan">
                    <span class="subscriptions-plan__name">{{ $themeSubscription->theme?->name }}</span>
                    <span class="subscriptions-badge subscriptions-badge--active">
                        {{ __('dashboard.status_active') }}
                    </span>
                </div>

                <dl class="subscriptions-grid">
                    <div>
                        <dt class="subscriptions-detail__label">{{ __('dashboard.price') }}</dt>
                        <dd class="subscriptions-detail__value">
                            {{ number_format((float) $themeSubscription->price, 2) }} {{ $themeSubscription->currency }}
                        </dd>
                    </div>
                    <div>
                        <dt class="subscriptions-detail__label">{{ __('dashboard.started_at') }}</dt>
                        <dd class="subscriptions-detail__value">{{ $themeSubscription->starts_at?->toDateString() }}</dd>
                    </div>
                    <div>
                        <dt class="subscriptions-detail__label">{{ __('dashboard.expires_at') }}</dt>
                        <dd class="subscriptions-detail__value">{{ $themeSubscription->expires_at?->toDateString() ?? '—' }}</dd>
                    </div>
                </dl>
            @else
                <p class="subscriptions-empty">{{ __('dashboard.no_active_theme') }}</p>
            @endif
        </section>

        {{-- Subscription history --}}
        <section class="subscriptions-card">
            <h2 class="subscriptions-card__title">{{ __('dashboard.subscription_history') }}</h2>

            @php($packages = $this->getPackages())

            @if ($packages->isEmpty())
                <p class="subscriptions-empty">{{ __('dashboard.no_active_package') }}</p>
            @else
                <div class="subscriptions-table__wrap">
                    <table class="subscriptions-table">
                        <thead>
                            <tr>
                                <th>{{ __('dashboard.package_name') }}</th>
                                <th>{{ __('dashboard.status') }}</th>
                                <th>{{ __('dashboard.price') }}</th>
                                <th>{{ __('dashboard.duration') }}</th>
                                <th>{{ __('dashboard.started_at') }}</th>
                                <th>{{ __('dashboard.expires_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packages as $package)
                                <tr>
                                    <td class="subscriptions-table__cell-name">{{ $package->package?->name }}</td>
                                    <td>
                                        <span class="subscriptions-badge {{ $statusModifier($package->status) }}">
                                            {{ __('dashboard.status_'.$package->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ number_format((float) $package->price, 2) }} {{ $package->currency?->code }}
                                    </td>
                                    <td>{{ $this->formatDuration($package) }}</td>
                                    <td>{{ $package->started_at?->toDateString() }}</td>
                                    <td>{{ $package->expires_at?->toDateString() ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
