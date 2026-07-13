<x-filament-panels::page>
    <div class="wa-page wa-connect">
        <div class="wa-connect__header">
            <p class="wa-connect__intro">{{ __('dashboard.messenger_connect_intro') }}</p>
        </div>

        <div class="wa-connect__grid">
            <article class="wa-connect-card">
                <div class="wa-connect-card__top">
                    <h3 class="wa-connect-card__title">{{ __('dashboard.messenger_connect_manual_title') }}</h3>
                    <p class="wa-connect-card__body">{{ __('dashboard.messenger_connect_manual_body') }}</p>
                    <ul class="wa-connect-card__list">
                        <li>{{ __('dashboard.messenger_connect_manual_point_1') }}</li>
                        <li>{{ __('dashboard.messenger_connect_manual_point_2') }}</li>
                        <li>{{ __('dashboard.messenger_connect_manual_point_3') }}</li>
                    </ul>
                </div>
                <div class="wa-connect-card__actions">
                    <x-filament::button wire:click="chooseManual">
                        {{ __('dashboard.messenger_connect_manual_cta') }}
                    </x-filament::button>
                </div>
            </article>

            @if ($this->isFacebookLoginConfigured())
                <article class="wa-connect-card wa-connect-card--recommended">
                    <div class="wa-connect-card__top">
                        <span class="wa-connect-card__badge">{{ __('dashboard.messenger_connect_recommended') }}</span>
                        <h3 class="wa-connect-card__title">{{ __('dashboard.messenger_connect_facebook_title') }}</h3>
                        <p class="wa-connect-card__body">{{ __('dashboard.messenger_connect_facebook_body') }}</p>
                        <ul class="wa-connect-card__list">
                            <li>{{ __('dashboard.messenger_connect_facebook_point_1') }}</li>
                            <li>{{ __('dashboard.messenger_connect_facebook_point_2') }}</li>
                            <li>{{ __('dashboard.messenger_connect_facebook_point_3') }}</li>
                        </ul>
                    </div>
                    <div class="wa-connect-card__actions">
                        <x-filament::button wire:click="chooseFacebookLogin" color="success">
                            {{ __('dashboard.messenger_connect_facebook_cta') }}
                        </x-filament::button>
                    </div>
                </article>
            @else
                <article class="wa-connect-card wa-connect-card--gated">
                    <div class="wa-connect-card__top">
                        <span class="wa-connect-card__badge wa-connect-card__badge--muted">{{ __('dashboard.messenger_connect_config_required') }}</span>
                        <h3 class="wa-connect-card__title">{{ __('dashboard.messenger_connect_facebook_title') }}</h3>
                        <p class="wa-connect-card__body">{{ __('dashboard.messenger_onboarding_config_required_body') }}</p>
                        <ul class="wa-connect-card__list">
                            <li>{{ __('dashboard.messenger_connect_facebook_point_1') }}</li>
                            <li>{{ __('dashboard.messenger_connect_facebook_point_2') }}</li>
                        </ul>
                    </div>
                    <div class="wa-connect-card__actions">
                        <x-filament::button wire:click="chooseFacebookLogin" color="gray">
                            {{ __('dashboard.messenger_connect_facebook_unavailable_cta') }}
                        </x-filament::button>
                    </div>
                </article>
            @endif
        </div>
    </div>
</x-filament-panels::page>
