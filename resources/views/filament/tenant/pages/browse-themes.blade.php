<x-filament-panels::page>
    <div class="themes-page">
        {{-- Subscribed Theme Banner --}}
        @php $subscribedTheme = $this->getSubscribedTheme(); @endphp

        @if ($subscribedTheme)
            <div class="theme-subscribed-banner">
                <div class="theme-subscribed-banner__info">
                    <x-filament::icon name="heroicon-o-check-circle" class="theme-subscribed-banner__icon" />
                    <span>
                        {{ __('dashboard.theme_subscribed_banner', ['name' => $subscribedTheme->name]) }}
                    </span>
                </div>
                @if ($subscribedTheme->preview_url)
                    <x-filament::button
                        tag="a"
                        href="{{ $subscribedTheme->preview_url }}"
                        target="_blank"
                        color="primary"
                        icon="heroicon-o-eye"
                        size="sm"
                    >
                        {{ __('dashboard.theme_preview') }}
                    </x-filament::button>
                @endif
            </div>
        @endif

        {{-- Filter Bar --}}
        <div class="themes-filter-bar">
            <div class="themes-filter-bar__section">
                <span class="themes-filter-bar__label">{{ __('dashboard.theme_filter_category') }}</span>
                <div class="themes-filter-bar__btns">
                    <button
                        type="button"
                        class="theme-filter-btn {{ blank($category) ? 'theme-filter-btn--active' : '' }}"
                        wire:click="$set('category', null)"
                    >
                        {{ __('dashboard.theme_filter_all') }}
                    </button>
                    @foreach ($this->getCategories() as $cat)
                        <button
                            type="button"
                            class="theme-filter-btn {{ $category === $cat->slug ? 'theme-filter-btn--active' : '' }}"
                            wire:click="$set('category', '{{ $cat->slug }}')"
                        >
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="themes-filter-bar__section">
                <span class="themes-filter-bar__label">{{ __('dashboard.theme_filter_price') }}</span>
                <div class="themes-filter-bar__btns">
                    <button
                        type="button"
                        class="theme-filter-btn {{ $priceFilter === 'all' ? 'theme-filter-btn--active' : '' }}"
                        wire:click="$set('priceFilter', 'all')"
                    >
                        {{ __('dashboard.theme_filter_all') }}
                    </button>
                    <button
                        type="button"
                        class="theme-filter-btn {{ $priceFilter === 'free' ? 'theme-filter-btn--active' : '' }}"
                        wire:click="$set('priceFilter', 'free')"
                    >
                        {{ __('dashboard.theme_free') }}
                    </button>
                    <button
                        type="button"
                        class="theme-filter-btn {{ $priceFilter === 'paid' ? 'theme-filter-btn--active' : '' }}"
                        wire:click="$set('priceFilter', 'paid')"
                    >
                        {{ __('dashboard.theme_filter_paid') }}
                    </button>
                </div>
            </div>

            @if ($category || $priceFilter !== 'all')
                <button
                    type="button"
                    class="theme-filter-btn theme-filter-btn--clear"
                    wire:click="clearFilters"
                >
                    &times; {{ __('dashboard.theme_filter_clear') }}
                </button>
            @endif
        </div>

        {{-- Themes Grid --}}
        @php $themes = $this->getThemes(); @endphp

        @if ($themes->isEmpty())
            <div class="themes-empty">
                <p>{{ __('dashboard.theme_no_themes') }}</p>
            </div>
        @else
            <div class="themes-grid">
                @foreach ($themes as $theme)
                    <article class="theme-card">
                        <div class="theme-card__image">
                            @if ($theme->image)
                                <img src="{{ asset('storage/' . $theme->image) }}" alt="{{ $theme->name }}">
                            @else
                                <div class="theme-card__image-placeholder">
                                    <x-filament::icon name="heroicon-o-swatch" class="h-12 w-12 text-gray-300" />
                                </div>
                            @endif
                        </div>

                        <div class="theme-card__info">
                            <h3 class="theme-card__name">{{ $theme->name }}</h3>

                            @if ($theme->description)
                                <p class="theme-card__description">{{ $theme->description }}</p>
                            @endif

                            <div class="theme-card__categories">
                                @foreach ($theme->categories as $cat)
                                    <span class="theme-card__cat-badge">{{ $cat->name }}</span>
                                @endforeach
                            </div>

                            <div class="theme-card__price">
                                @if ($theme->is_free)
                                    <span class="theme-card__price-free">{{ __('dashboard.theme_free') }}</span>
                                @else
                                    <span class="theme-card__price-amount">${{ number_format((float) $theme->price, 2) }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="theme-card__actions">
                            @if ($theme->preview_url)
                                <x-filament::button
                                    tag="a"
                                    href="{{ $theme->preview_url }}"
                                    target="_blank"
                                    color="gray"
                                    icon="heroicon-o-eye"
                                >
                                    {{ __('dashboard.theme_preview') }}
                                </x-filament::button>
                            @endif

                            <x-filament::button
                                wire:click="confirmSubscribe({{ $theme->id }})"
                                color="primary"
                            >
                                {{ __('dashboard.theme_subscribe') }}
                            </x-filament::button>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Confirm Subscribe Modal --}}
    <x-filament::modal id="confirm-subscribe" width="md">
        <x-slot name="heading">
            {{ __('dashboard.theme_confirm_subscribe_title') }}
        </x-slot>

        <x-slot name="description">
            {{ __('dashboard.theme_confirm_subscribe') }}
        </x-slot>

        <x-slot name="footerActions">
            <x-filament::button
                color="gray"
                wire:click="$dispatch('close-modal', { id: 'confirm-subscribe' })"
            >
                {{ __('dashboard.cancel') }}
            </x-filament::button>

            <x-filament::button
                color="primary"
                wire:click="subscribe"
            >
                {{ __('dashboard.theme_confirm_subscribe_yes') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</x-filament-panels::page>
