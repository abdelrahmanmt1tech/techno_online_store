<x-filament-panels::page>
    <style>{!! file_get_contents(resource_path('css/subscriptions-page.css')) !!}</style>

    @php
        $activePackages = $this->getActivePackages();
        $packages = $this->getPackages();
        $themeSubscription = $this->getThemeSubscription();
        $enabledModules = $this->getEnabledModules();
        $statusModifier = static fn (string $status): string => 'subs-badge--'.(match ($status) {
            'trial', 'active', 'expired', 'cancelled' => $status,
            default => 'cancelled',
        });
        $nearestExpiry = $activePackages
            ->filter(fn ($p) => $p->expires_at !== null)
            ->sortBy('expires_at')
            ->first();
        $nearestDays = $nearestExpiry ? $this->daysRemaining($nearestExpiry->expires_at) : null;
    @endphp

    <div class="subs-page" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
        <header class="subs-hero">
            <div class="subs-hero__glow" aria-hidden="true"></div>
            <div class="subs-hero__shine" aria-hidden="true"></div>

            <div class="subs-hero__content">
                <p class="subs-hero__eyebrow">{{ __('dashboard.subscriptions_group') }}</p>
                <h1 class="subs-hero__title">{{ __('dashboard.my_subscriptions') }}</h1>
                <p class="subs-hero__subtitle">{{ __('dashboard.subscriptions_page_intro') }}</p>
            </div>

            <div class="subs-hero__stats">
                <div class="subs-stat" style="--i:0">
                    <span class="subs-stat__value">{{ $activePackages->count() }}</span>
                    <span class="subs-stat__label">{{ __('dashboard.active_packages_count') }}</span>
                </div>
                <div class="subs-stat" style="--i:1">
                    <span class="subs-stat__value">{{ count($enabledModules) }}</span>
                    <span class="subs-stat__label">{{ __('dashboard.enabled_modules') }}</span>
                </div>
                <div class="subs-stat" style="--i:2">
                    <span class="subs-stat__value">
                        @if ($nearestDays === null)
                            —
                        @elseif ($nearestDays < 0)
                            0
                        @else
                            {{ $nearestDays }}
                        @endif
                    </span>
                    <span class="subs-stat__label">{{ __('dashboard.days_until_nearest_expiry') }}</span>
                </div>
            </div>
        </header>

        @if ($enabledModules)
            <section class="subs-modules-bar" style="--i:1">
                <span class="subs-modules-bar__label">{{ __('dashboard.enabled_modules') }}</span>
                <div class="subs-modules-bar__list">
                    @foreach ($enabledModules as $module)
                        <span class="subs-chip">{{ $module }}</span>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="subs-section" style="--i:2">
            <div class="subs-section__head">
                <h2 class="subs-section__title">{{ __('dashboard.active_packages') }}</h2>
                <p class="subs-section__hint">{{ __('dashboard.active_packages_hint') }}</p>
            </div>

            @if ($activePackages->isEmpty())
                <div class="subs-empty">
                    <div class="subs-empty__orb" aria-hidden="true"></div>
                    <p class="subs-empty__title">{{ __('dashboard.no_active_package') }}</p>
                    <p class="subs-empty__text">{{ __('dashboard.no_active_package_hint') }}</p>
                </div>
            @else
                <div class="subs-grid">
                    @foreach ($activePackages as $index => $package)
                        @php
                            $days = $this->daysRemaining($package->expires_at);
                            $progress = $this->progressPercent($package);
                            $urgency = $this->urgencyClass($days, $package->status);
                        @endphp
                        <article class="subs-card {{ $urgency }}" style="--i:{{ $index }}">
                            <div class="subs-card__sheen" aria-hidden="true"></div>

                            <div class="subs-card__top">
                                <div>
                                    <p class="subs-card__module">{{ $this->packageModulesLabel($package) }}</p>
                                    <h3 class="subs-card__name">{{ $package->package?->name }}</h3>
                                </div>
                                <span class="subs-badge {{ $statusModifier($package->status) }}">
                                    {{ __('dashboard.status_'.$package->status) }}
                                </span>
                            </div>

                            <div class="subs-card__ring-wrap">
                                <div
                                    class="subs-ring"
                                    style="--p: {{ $progress }}"
                                    role="img"
                                    aria-label="{{ __('dashboard.remaining_percent', ['percent' => $progress]) }}"
                                >
                                    <div class="subs-ring__inner">
                                        <span class="subs-ring__days">
                                            @if ($days === null)
                                                ∞
                                            @elseif ($days < 0)
                                                0
                                            @else
                                                {{ $days }}
                                            @endif
                                        </span>
                                        <span class="subs-ring__unit">{{ __('dashboard.days_left') }}</span>
                                    </div>
                                </div>
                            </div>

                            <dl class="subs-meta">
                                <div>
                                    <dt>{{ __('dashboard.price') }}</dt>
                                    <dd>{{ number_format((float) $package->price, 2) }} {{ $package->currency?->code }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('dashboard.duration') }}</dt>
                                    <dd>{{ $this->formatDuration($package) }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('dashboard.started_at') }}</dt>
                                    <dd>{{ $package->started_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>{{ __('dashboard.expires_at') }}</dt>
                                    <dd class="subs-meta__expiry">
                                        {{ $package->expires_at?->translatedFormat('d M Y') ?? '—' }}
                                    </dd>
                                </div>
                            </dl>

                            @if ($package->trial_ends_at && $package->status === 'trial')
                                <p class="subs-card__trial">
                                    {{ __('dashboard.trial_ends_at') }}:
                                    <strong>{{ $package->trial_ends_at->translatedFormat('d M Y') }}</strong>
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="subs-section" style="--i:3">
            <div class="subs-section__head">
                <h2 class="subs-section__title">{{ __('dashboard.theme_subscription') }}</h2>
            </div>

            @if ($themeSubscription)
                <article class="subs-theme">
                    <div class="subs-theme__glow" aria-hidden="true"></div>
                    <div class="subs-theme__body">
                        <div>
                            <p class="subs-theme__label">{{ __('dashboard.theme_subscription') }}</p>
                            <h3 class="subs-theme__name">{{ $themeSubscription->theme?->name }}</h3>
                        </div>
                        <span class="subs-badge subs-badge--active">{{ __('dashboard.status_active') }}</span>
                    </div>
                    <dl class="subs-meta subs-meta--theme">
                        <div>
                            <dt>{{ __('dashboard.price') }}</dt>
                            <dd>{{ number_format((float) $themeSubscription->price, 2) }} {{ $themeSubscription->currency }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('dashboard.started_at') }}</dt>
                            <dd>{{ $themeSubscription->starts_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('dashboard.expires_at') }}</dt>
                            <dd>{{ $themeSubscription->expires_at?->translatedFormat('d M Y') ?? '—' }}</dd>
                        </div>
                    </dl>
                </article>
            @else
                <div class="subs-empty subs-empty--compact">
                    <p class="subs-empty__title">{{ __('dashboard.no_active_theme') }}</p>
                </div>
            @endif
        </section>

        <section class="subs-section" style="--i:4">
            <div class="subs-section__head">
                <h2 class="subs-section__title">{{ __('dashboard.subscription_history') }}</h2>
                <p class="subs-section__hint">{{ __('dashboard.subscription_history_hint') }}</p>
            </div>

            @if ($packages->isEmpty())
                <div class="subs-empty subs-empty--compact">
                    <p class="subs-empty__title">{{ __('dashboard.no_subscription_history') }}</p>
                </div>
            @else
                <div class="subs-history">
                    <div class="subs-history__scroll">
                        <table class="subs-table">
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
                                @foreach ($packages as $index => $package)
                                    <tr style="--i:{{ $index }}">
                                        <td class="subs-table__name">{{ $package->package?->name }}</td>
                                        <td>
                                            <span class="subs-badge {{ $statusModifier($package->status) }}">
                                                {{ __('dashboard.status_'.$package->status) }}
                                            </span>
                                        </td>
                                        <td>{{ number_format((float) $package->price, 2) }} {{ $package->currency?->code }}</td>
                                        <td>{{ $this->formatDuration($package) }}</td>
                                        <td>{{ $package->started_at?->translatedFormat('d M Y') ?? '—' }}</td>
                                        <td>{{ $package->expires_at?->translatedFormat('d M Y') ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
