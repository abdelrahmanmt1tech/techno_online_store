<div class="wa-connect">
    <p class="wa-connect__intro">{{ __('dashboard.whatsapp_connect_intro') }}</p>

    <div class="wa-connect__grid">
        <article class="wa-connect-card">
            <h3 class="wa-connect-card__title">{{ __('dashboard.whatsapp_connect_manual_title') }}</h3>
            <p class="wa-connect-card__body">{{ __('dashboard.whatsapp_connect_manual_body') }}</p>
            <ul class="wa-connect-card__list">
                <li>{{ __('dashboard.whatsapp_connect_manual_point_1') }}</li>
                <li>{{ __('dashboard.whatsapp_connect_manual_point_2') }}</li>
                <li>{{ __('dashboard.whatsapp_connect_manual_point_3') }}</li>
            </ul>
            <x-filament::button wire:click="chooseManual">
                {{ __('dashboard.whatsapp_connect_manual_cta') }}
            </x-filament::button>
        </article>

        <article class="wa-connect-card wa-connect-card--recommended">
            <span class="wa-connect-card__badge">{{ __('dashboard.whatsapp_connect_recommended') }}</span>
            <h3 class="wa-connect-card__title">{{ __('dashboard.whatsapp_connect_api_only_title') }}</h3>
            <p class="wa-connect-card__body">{{ __('dashboard.whatsapp_connect_api_only_body') }}</p>
            <ul class="wa-connect-card__list">
                <li>{{ __('dashboard.whatsapp_connect_api_only_point_1') }}</li>
                <li>{{ __('dashboard.whatsapp_connect_api_only_point_2') }}</li>
                <li>{{ __('dashboard.whatsapp_connect_api_only_point_3') }}</li>
            </ul>
            <x-filament::button wire:click="chooseApiOnly" color="success">
                {{ __('dashboard.whatsapp_connect_api_only_cta') }}
            </x-filament::button>
        </article>

        <article class="wa-connect-card wa-connect-card--gated">
            <span class="wa-connect-card__badge wa-connect-card__badge--muted">{{ __('dashboard.whatsapp_connect_coming_soon') }}</span>
            <h3 class="wa-connect-card__title">{{ __('dashboard.whatsapp_connect_coexistence_title') }}</h3>
            <p class="wa-connect-card__body">{{ __('dashboard.whatsapp_connect_coexistence_body') }}</p>
            <ul class="wa-connect-card__list">
                <li>{{ __('dashboard.whatsapp_connect_coexistence_point_1') }}</li>
                <li>{{ __('dashboard.whatsapp_connect_coexistence_point_2') }}</li>
            </ul>
            <x-filament::button wire:click="chooseCoexistence" color="gray">
                {{ __('dashboard.whatsapp_connect_coexistence_cta') }}
            </x-filament::button>
        </article>
    </div>
</div>
