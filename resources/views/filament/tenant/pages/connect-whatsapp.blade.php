<x-filament-panels::page>
    <div class="wa-page wa-connect">
        <div class="wa-connect__header">
            <p class="wa-connect__intro">{{ __('dashboard.whatsapp_connect_intro') }}</p>
        </div>

        <div class="wa-connect__grid">
            <article class="wa-connect-card">
                <div class="wa-connect-card__top">
                    <h3 class="wa-connect-card__title">{{ __('dashboard.whatsapp_connect_manual_title') }}</h3>
                    <p class="wa-connect-card__body">{{ __('dashboard.whatsapp_connect_manual_body') }}</p>
                    <ul class="wa-connect-card__list">
                        <li>{{ __('dashboard.whatsapp_connect_manual_point_1') }}</li>
                        <li>{{ __('dashboard.whatsapp_connect_manual_point_2') }}</li>
                        <li>{{ __('dashboard.whatsapp_connect_manual_point_3') }}</li>
                    </ul>
                </div>
                <div class="wa-connect-card__actions">
                    <x-filament::button wire:click="chooseManual">
                        {{ __('dashboard.whatsapp_connect_manual_cta') }}
                    </x-filament::button>
                </div>
            </article>

            <article class="wa-connect-card wa-connect-card--recommended">
                <div class="wa-connect-card__top">
                    <span class="wa-connect-card__badge">{{ __('dashboard.whatsapp_connect_recommended') }}</span>
                    <h3 class="wa-connect-card__title">{{ __('dashboard.whatsapp_connect_api_only_title') }}</h3>
                    <p class="wa-connect-card__body">{{ __('dashboard.whatsapp_connect_api_only_body') }}</p>
                    <ul class="wa-connect-card__list">
                        <li>{{ __('dashboard.whatsapp_connect_api_only_point_1') }}</li>
                        <li>{{ __('dashboard.whatsapp_connect_api_only_point_2') }}</li>
                        <li>{{ __('dashboard.whatsapp_connect_api_only_point_3') }}</li>
                    </ul>
                </div>
                <div class="wa-connect-card__actions">
                    <x-filament::button wire:click="chooseApiOnly" color="success">
                        {{ __('dashboard.whatsapp_connect_api_only_cta') }}
                    </x-filament::button>
                </div>
            </article>

            @if ($this->isCoexistenceConfigured())
                <article class="wa-connect-card wa-connect-card--coexistence">
                    <div class="wa-connect-card__top">
                        <h3 class="wa-connect-card__title">{{ __('dashboard.whatsapp_connect_coexistence_title') }}</h3>
                        <p class="wa-connect-card__body">{{ __('dashboard.whatsapp_connect_coexistence_body') }}</p>
                        <ul class="wa-connect-card__list">
                            <li>{{ __('dashboard.whatsapp_connect_coexistence_point_1') }}</li>
                            <li>{{ __('dashboard.whatsapp_connect_coexistence_point_2') }}</li>
                            <li>{{ __('dashboard.whatsapp_connect_coexistence_point_3') }}</li>
                        </ul>
                    </div>
                    <div class="wa-connect-card__actions">
                        <x-filament::button wire:click="chooseCoexistence" color="primary">
                            {{ __('dashboard.whatsapp_connect_coexistence_cta') }}
                        </x-filament::button>
                    </div>
                </article>
            @else
                <article class="wa-connect-card wa-connect-card--gated">
                    <div class="wa-connect-card__top">
                        <span class="wa-connect-card__badge wa-connect-card__badge--muted">{{ __('dashboard.whatsapp_connect_config_required') }}</span>
                        <h3 class="wa-connect-card__title">{{ __('dashboard.whatsapp_connect_coexistence_title') }}</h3>
                        <p class="wa-connect-card__body">{{ __('dashboard.whatsapp_onboarding_coexistence_config_required_body') }}</p>
                        <ul class="wa-connect-card__list">
                            <li>{{ __('dashboard.whatsapp_connect_coexistence_point_1') }}</li>
                            <li>{{ __('dashboard.whatsapp_connect_coexistence_point_2') }}</li>
                        </ul>
                    </div>
                    <div class="wa-connect-card__actions">
                        <x-filament::button wire:click="chooseCoexistence" color="gray">
                            {{ __('dashboard.whatsapp_connect_coexistence_unavailable_cta') }}
                        </x-filament::button>
                    </div>
                </article>
            @endif
        </div>
    </div>
</x-filament-panels::page>
