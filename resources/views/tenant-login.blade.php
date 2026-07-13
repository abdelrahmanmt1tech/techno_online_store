<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}" class="fi">
    <head>
        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>{{ __('dashboard.login') }} - {{ config('app.name') }}</title>
        <link
            href="{{ asset('css/filament/filament/app.css') }}"
            rel="stylesheet"
            data-navigate-track
        />
        @filamentStyles
    </head>
    <body class="fi-body">
        <div class="fi-simple-layout">
            <div class="fi-simple-main-ctn">
                <main class="fi-simple-main fi-width-max-w-xl">
                    <div class="fi-simple-page">
                        <div class="fi-simple-page-content">
                            <header class="fi-simple-header">
                                <div class="fi-logo">
                                    {{ config('app.name') }}
                                </div>
                                <h1 class="fi-simple-header-heading">
                                    {{ __('dashboard.login') }}
                                </h1>
                            </header>

                            @if (isset($errors) && $errors->any())
                                <div style="margin-bottom: 1.5rem;">
                                    <ul data-validation-error class="fi-fo-field-wrp-error-list">
                                        @foreach ($errors->all() as $error)
                                            <li class="fi-fo-field-wrp-error-message">{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('tenant-login.login') }}" class="fi-form">
                                @csrf

                                <div class="fi-fo-field">
                                    <div class="fi-fo-field-label-col">
                                        <label for="email" class="fi-fo-field-label">
                                            <span class="fi-fo-field-label-content">
                                                {{ __('dashboard.email') }}
                                            </span>
                                        </label>
                                    </div>
                                    <div class="fi-fo-field-content-col">
                                        <div class="fi-fo-field-content">
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="email"
                                                    name="email"
                                                    id="email"
                                                    value="{{ old('email') }}"
                                                    required
                                                    autofocus
                                                />
                                            </x-filament::input.wrapper>
                                        </div>
                                    </div>
                                </div>

                                <div class="fi-fo-field">
                                    <div class="fi-fo-field-label-col">
                                        <label for="password" class="fi-fo-field-label">
                                            <span class="fi-fo-field-label-content">
                                                {{ __('dashboard.password') }}
                                            </span>
                                        </label>
                                    </div>
                                    <div class="fi-fo-field-content-col">
                                        <div class="fi-fo-field-content">
                                            <x-filament::input.wrapper>
                                                <x-filament::input
                                                    type="password"
                                                    name="password"
                                                    id="password"
                                                    required
                                                    autocomplete="current-password"
                                                />
                                            </x-filament::input.wrapper>
                                        </div>
                                    </div>
                                </div>

                                <x-filament::button color="primary" size="md" type="submit" class="w-full">
                                    {{ __('dashboard.login') }}
                                </x-filament::button>
                            </form>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
