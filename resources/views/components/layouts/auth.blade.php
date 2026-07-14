@props([
    'pageTitle' => '',
    'heading' => '',
])

@php
    $brandName = config('app.name');
    $siteLogo = \App\Models\Setting::where('key', 'site_logo')->value('value');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
    @class(['fi', 'dark' => filament()->hasDarkMode() && filament()->hasDarkModeForced()])>

<head>
    <meta charset="utf-8" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $pageTitle ?? '' }} - {{ $brandName }}</title>

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
            box-shadow: 0 0 0 3px rgba(5, 130, 53, 0.25) !important;
            border-radius: 12px !important;
        }

        .fi-input {
            border-radius: 12px !important;
        }
    </style>

    @if (!filament()->hasDarkMode())
        <script>localStorage.setItem('theme', 'light')</script>
    @elseif (filament()->hasDarkModeForced())
        <script>localStorage.setItem('theme', 'dark')</script>
    @else
        <script>
            const loadDarkMode = () => {
                window.theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)
                if (window.theme === 'dark' || (window.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
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
                                {{ $heading ?? '' }}
                            </h1>
                        </header>

                        @if (session('success'))
                            <div style="margin-bottom: 1.5rem; padding: 0.75rem 1rem; border-radius: 12px; background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </div>

    @livewire(Filament\Livewire\Notifications::class)
    @filamentScripts(withCore: true)

    @if (filament()->hasDarkMode() && !filament()->hasDarkModeForced())
        <script>loadDarkMode()</script>
    @endif
</body>
</html>
