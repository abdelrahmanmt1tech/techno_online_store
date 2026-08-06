@php
    $brandName = config('app.name');
    $siteLogo = \App\Models\Setting::where('key', 'site_logo')->value('value');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
    @class([
        'fi',
        'dark' => filament()->hasDarkMode() && filament()->hasDarkModeForced(),
    ])>

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ __('dashboard.login') }} - {{ $brandName }}</title>

    <style>
        [x-cloak=''],
        [x-cloak='x-cloak'],
        [x-cloak='1'] {
            display: none !important;
        }

        [x-cloak='inline-flex'] {
            display: inline-flex !important;
        }

        @media (max-width: 1023px) {
            [x-cloak='-lg'] {
                display: none !important;
            }
        }

        @media (min-width: 1024px) {
            [x-cloak='lg'] {
                display: none !important;
            }
        }
    </style>

    @filamentStyles

    {{ filament()->getTheme()->getHtml() }}
    {{ filament()->getFontPreloadHtml() }}
    {{ filament()->getMonoFontPreloadHtml() }}
    {{ filament()->getSerifFontPreloadHtml() }}
    {{ filament()->getFontHtml() }}
    {{ filament()->getMonoFontHtml() }}
    {{ filament()->getSerifFontHtml() }}

    <style>
        :root {
            --font-family: '{!! filament()->getFontFamily() !!}';
            --mono-font-family: '{!! filament()->getMonoFontFamily() !!}';
            --serif-font-family: '{!! filament()->getSerifFontFamily() !!}';
            --sidebar-width: {{ filament()->getSidebarWidth() }};
            --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
            --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};

        }

        html.fi {
            --livewire-progress-bar-color: var(--primary-500);
        }

        .fi-fo-field {
            margin-bottom: 0.9rem;
        }

        .fi-fo-field:last-child {
            margin-bottom: 0;
        }

        .fi-ac-btn-action,
        .fi-btn {
            background-color: #166534 !important;
            border-color: #166534 !important;
            color: white !important;
            border-radius: 12px !important;
        }

        .fi-ac-btn-action:hover,
        .fi-btn:hover {
            background-color: #14532d !important;
            border-color: #14532d !important;
        }

        .fi-input-wrp {
            border-radius: 12px !important;
            transition: all 0.2s ease;
        }

        .fi-input-wrp:focus-within {
            border-color: #093419 !important;
            /* Dark Green */
            box-shadow: 0 0 0 3px rgba(5, 130, 53, 0.25) !important;
            border-radius: 12px !important;
        }

        .fi-input {
            border-radius: 12px !important;
        }
    </style>

    @if (!filament()->hasDarkMode())
        <script>
            localStorage.setItem('theme', 'light')
        </script>
    @elseif (filament()->hasDarkModeForced())
        <script>
            localStorage.setItem('theme', 'dark')
        </script>
    @else
        <script>
            const loadDarkMode = () => {
                window.theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

                if (
                    window.theme === 'dark' ||
                    (window.theme === 'system' &&
                        window.matchMedia('(prefers-color-scheme: dark)')
                        .matches)
                ) {
                    document.documentElement.classList.add('dark')
                }
            }

            loadDarkMode()

            document.addEventListener('livewire:navigated', loadDarkMode)
        </script>
    @endif
</head>

<body @class(['fi-body', 'fi-panel-' . filament()->getId()])>
    <div class="fi-simple-layout">
        <div class="fi-simple-main-ctn">
            <main class="fi-simple-main fi-width-lg">
                <div class="fi-simple-page">
                    <div class="fi-simple-page-content py-12">
                        <header class="fi-simple-header">
                            @if ($siteLogo)
                                <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $brandName }}"
                                    class="fi-logo" style="height: 4rem;" />
                            @else
                                <div class="fi-logo">{{ $brandName }}</div>
                            @endif

                            <h1 class="fi-simple-header-heading">
                                {{ __('dashboard.login') }}
                            </h1>
                        </header>

                        <form x-data="{ submitting: false }" @submit="submitting = true" method="POST"
                            action="{{ route('tenant-login.login') }}" class="fi-form mt-8 space-y-6"> @csrf

                            <div class="fi-fo-field">
                                <div class="fi-fo-field-label-col">
                                    <label for="email" class="fi-fo-field-label">
                                        <span class="fi-fo-field-label-content">
                                            {{ __('filament-panels::auth/pages/login.form.email.label') }}
                                        </span>
                                    </label>
                                </div>
                                <div class="fi-fo-field-content-col">
                                    <div class="fi-fo-field-content">
                                        <x-filament::input.wrapper :invalid="$errors->has('email')">
                                            <x-filament::input type="email" name="email" id="email"
                                                value="{{ old('email') }}" required autofocus autocomplete="email" />
                                        </x-filament::input.wrapper>

                                        @error('email')
                                            <p class="fi-fo-field-wrp-error-message">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="fi-fo-field">
                                <div class="fi-fo-field-label-col">
                                    <label for="password" class="fi-fo-field-label">
                                        <span class="fi-fo-field-label-content">
                                            {{ __('filament-panels::auth/pages/login.form.password.label') }}
                                        </span>
                                    </label>
                                </div>
                                <div class="fi-fo-field-content-col">
                                <div class="fi-fo-field-content">
                                    <x-filament::input.wrapper :invalid="$errors->has('password')" x-data="{ showPassword: false }">
                                        <x-filament::input type="password" name="password" id="password" required
                                            autocomplete="current-password" x-bind:type="showPassword ? 'text' : 'password'" />

                                        <x-slot name="suffix">
                                            <button type="button" tabindex="-1" x-on:click="showPassword = !showPassword"
                                                x-bind:aria-label="showPassword ? @js(__('filament-forms::components.text_input.actions.hide_password.label')) : @js(__('filament-forms::components.text_input.actions.show_password.label'))"
                                                style="display:inline-flex;align-items:center;justify-content:center;cursor:pointer;color:#6b7280;background:transparent;border:none;padding:0.25rem;border-radius:0.375rem;"
                                                x-on:mouseenter="$el.style.color='#374151'"
                                                x-on:mouseleave="$el.style.color='#6b7280'">
                                                <svg x-show="!showPassword" class="fi-icon fi-size-md" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                                    <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"></path>
                                                    <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"></path>
                                                </svg>
                                                <svg x-show="showPassword" x-cloak class="fi-icon fi-size-md" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
                                                    <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l14.5 14.5a.75.75 0 1 0 1.06-1.06l-1.745-1.745a10.029 10.029 0 0 0 3.3-4.38 1.651 1.651 0 0 0 0-1.185A10.004 10.004 0 0 0 9.999 3a9.956 9.956 0 0 0-4.744 1.194L3.28 2.22ZM7.752 6.69l1.092 1.092a2.5 2.5 0 0 1 3.374 3.373l1.091 1.092a4 4 0 0 0-5.557-5.557Zm-2.201 2.201a4 4 0 0 0 5.355 5.355l-1.066-1.066a2.5 2.5 0 0 1-3.223-3.223l-1.066-1.066Z" clip-rule="evenodd"/>
                                                    <path d="m13.411 15.472 1.018 1.018a.75.75 0 0 0 1.03-.032A9.96 9.96 0 0 0 18.671 14.59a.75.75 0 0 0-.001-.158A10.005 10.005 0 0 0 13.41 15.472Z"/>
                                                </svg>
                                            </button>
                                        </x-slot>
                                    </x-filament::input.wrapper>

                                    @error('password')
                                        <p class="fi-fo-field-wrp-error-message">{{ $message }}</p>
                                    @enderror
                                </div>
                                </div>
                            </div>

                            <div class="fi-form-actions">
                                <x-filament::button type="submit" color="primary" size="md" class="w-full text-center"
                                    x-bind:disabled="submitting"
                                    x-bind:class="{ 'opacity-50 cursor-not-allowed': submitting }">
                                    <span x-show="!submitting">
                                        {{ __('filament-panels::auth/pages/login.form.actions.authenticate.label') }}
                                    </span>

                                    <span x-show="submitting">
                                        {{ __('dashboard.signing_in') }}
                                    </span>
                                </x-filament::button>
                            </div>

                            <div style="text-align: center; margin-top: 0.5rem;">
                                <a href="{{ route('tenant.forgot-password.form') }}" style="font-size: 0.875rem; color: #166534; text-decoration: none; font-weight: 500;">
                                    {{ __('dashboard.forgot_password') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    @livewire(Filament\Livewire\Notifications::class)

    @filamentScripts(withCore: true)

    @if (filament()->hasDarkMode() && !filament()->hasDarkModeForced())
        <script>
            loadDarkMode()
        </script>
    @endif
</body>

</html>
