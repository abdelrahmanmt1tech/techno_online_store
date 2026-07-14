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
                                        <x-filament::input.wrapper :invalid="$errors->has('password')">
                                            <x-filament::input type="password" name="password" id="password" required
                                                autocomplete="current-password" />
                                        </x-filament::input.wrapper>

                                        @error('password')
                                            <p class="fi-fo-field-wrp-error-message">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="fi-form-actions">
                                <x-filament::button type="submit" color="primary" size="md" class="w-full"
                                    x-bind:disabled="submitting"
                                    x-bind:class="{ 'opacity-50 cursor-not-allowed': submitting }">
                                    <span x-show="!submitting">
                                        {{ __('filament-panels::auth/pages/login.form.actions.authenticate.label') }}
                                    </span>

                                    <span x-show="submitting">
                                        جاري تسجيل الدخول...
                                    </span>
                                </x-filament::button>
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
